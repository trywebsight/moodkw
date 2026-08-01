<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Governorate;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KuwaitLocationSeeder extends Seeder
{
    /**
     * Maps Open Admin Data governorate names to our internal names.
     *
     * @see database/seeders/data/kuwait-hierarchy.json
     */
    private const GOVERNORATE_MAP = [
        'Al-Ahmadi' => ['name' => 'Ahmadi', 'name_ar' => 'الأحمدي'],
        'AL-Jahra' => ['name' => 'Jahra', 'name_ar' => 'الجهراء'],
        'Capital' => ['name' => 'Capital', 'name_ar' => 'العاصمة'],
        'Farwaniyah' => ['name' => 'Farwaniya', 'name_ar' => 'الفروانية'],
        'Hawally' => ['name' => 'Hawalli', 'name_ar' => 'حولي'],
        'Mubarak AL-Kabeer' => ['name' => 'Mubarak Al-Kabeer', 'name_ar' => 'مبارك الكبير'],
    ];

    public function run(): void
    {
        $path = database_path('seeders/data/kuwait-hierarchy.json');

        if (! is_file($path)) {
            $this->command?->error('Missing Kuwait hierarchy data at: '.$path);

            return;
        }

        $hierarchy = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $officialKeys = $this->seedFromHierarchy($hierarchy);
        $this->cleanupLegacyAreas($officialKeys);
        $this->deduplicateAreas();
    }

    /**
     * @return array<string, true> Keys of official areas: "{governorate}|{normalized_ar}"
     */
    private function seedFromHierarchy(array $hierarchy): array
    {
        $officialKeys = [];

        foreach ($hierarchy['data'] as $governorateData) {
            $sourceName = $governorateData['name']['en'];
            $mapping = self::GOVERNORATE_MAP[$sourceName] ?? null;

            if ($mapping === null) {
                $this->command?->warn("Skipping unknown governorate: {$sourceName}");

                continue;
            }

            $governorate = Governorate::query()->firstOrCreate(
                ['name' => $mapping['name']],
                [
                    'name_ar' => $mapping['name_ar'],
                    'is_active' => true,
                ],
            );

            if ($governorate->name_ar !== $mapping['name_ar']) {
                $governorate->update(['name_ar' => $mapping['name_ar']]);
            }

            foreach ($governorateData['area'] as $areaData) {
                $nameEn = $this->normalizeEnglishName(
                    $areaData['name']['en'],
                    $areaData['name']['slug'] ?? '',
                );
                $nameAr = trim($areaData['name']['local']);
                $normalizedAr = $this->normalizeArabic($nameAr);

                if ($normalizedAr !== '') {
                    $officialKeys[$mapping['name'].'|'.$normalizedAr] = true;
                }

                $area = $this->findAreaInGovernorate($governorate->id, $nameEn, $nameAr);

                if ($area === null) {
                    Area::query()->create([
                        'governorate_id' => $governorate->id,
                        'name' => $nameEn,
                        'name_ar' => $nameAr !== '' ? $nameAr : null,
                        'is_active' => true,
                    ]);

                    continue;
                }

                $updates = [];

                if ($area->name !== $nameEn) {
                    $updates['name'] = $nameEn;
                }

                if ($nameAr !== '' && $area->name_ar !== $nameAr) {
                    $updates['name_ar'] = $nameAr;
                }

                if ($updates !== []) {
                    $area->update($updates);
                }
            }
        }

        return $officialKeys;
    }

    /**
     * @param  array<string, true>  $officialKeys
     */
    private function cleanupLegacyAreas(array $officialKeys): void
    {
        $governoratesByName = Governorate::query()->pluck('id', 'name');

        Area::query()
            ->with('governorate')
            ->withCount('orders')
            ->orderBy('id')
            ->chunkById(100, function ($areas) use ($officialKeys, $governoratesByName) {
                foreach ($areas as $area) {
                    $govName = $area->governorate->name;
                    $normalizedAr = $this->normalizeArabic($area->name_ar ?? '');

                    $isOfficial = $normalizedAr !== ''
                        && isset($officialKeys[$govName.'|'.$normalizedAr]);

                    if ($isOfficial) {
                        continue;
                    }

                    if ($area->orders_count > 0) {
                        $replacement = $this->findOfficialReplacement($area, $officialKeys, $governoratesByName);

                        if ($replacement !== null) {
                            Order::query()
                                ->where('area_id', $area->id)
                                ->update([
                                    'area_id' => $replacement->id,
                                    'governorate_id' => $replacement->governorate_id,
                                ]);
                        }

                        $area->update(['is_active' => false]);

                        continue;
                    }

                    $area->delete();
                }
            });
    }

    /**
     * @param  array<string, true>  $officialKeys
     */
    private function findOfficialReplacement(Area $area, array $officialKeys, \Illuminate\Support\Collection $governoratesByName): ?Area
    {
        $normalizedAr = $this->normalizeArabic($area->name_ar ?? '');

        if ($normalizedAr === '') {
            return null;
        }

        foreach ($officialKeys as $key => $_) {
            [$govName, $officialAr] = explode('|', $key, 2);

            if ($officialAr !== $normalizedAr) {
                continue;
            }

            $governorateId = $governoratesByName->get($govName);

            if ($governorateId === null) {
                continue;
            }

            return Area::query()
                ->where('governorate_id', $governorateId)
                ->whereRaw(
                    'REPLACE(REPLACE(REPLACE(REPLACE(name_ar, "أ", "ا"), "إ", "ا"), "آ", "ا"), "ى", "ي") = ?',
                    [$normalizedAr],
                )
                ->first();
        }

        return null;
    }

    private function deduplicateAreas(): void
    {
        $grouped = Area::query()
            ->with('governorate')
            ->withCount('orders')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Area $area) => $area->governorate_id.'|'.$this->areaDedupeKey($area));

        foreach ($grouped as $duplicates) {
            if ($duplicates->count() <= 1) {
                continue;
            }

            $keeper = $duplicates->first();

            foreach ($duplicates->slice(1) as $duplicate) {
                if ($duplicate->orders_count > 0) {
                    Order::query()
                        ->where('area_id', $duplicate->id)
                        ->update([
                            'area_id' => $keeper->id,
                            'governorate_id' => $keeper->governorate_id,
                        ]);
                }

                $duplicate->delete();
            }
        }
    }

    private function areaDedupeKey(Area $area): string
    {
        $normalizedAr = $this->normalizeArabic($area->name_ar ?? '');

        if ($normalizedAr !== '') {
            return 'ar:'.$normalizedAr;
        }

        return 'en:'.strtolower($area->name);
    }

    private function findAreaInGovernorate(int $governorateId, string $nameEn, string $nameAr): ?Area
    {
        $normalizedAr = $this->normalizeArabic($nameAr);

        return Area::query()
            ->where('governorate_id', $governorateId)
            ->where(function ($query) use ($nameEn, $nameAr, $normalizedAr) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($nameEn)]);

                if ($nameAr !== '') {
                    $query->orWhere('name_ar', $nameAr);

                    if ($normalizedAr !== '') {
                        $query->orWhereRaw(
                            'REPLACE(REPLACE(REPLACE(REPLACE(name_ar, "أ", "ا"), "إ", "ا"), "آ", "ا"), "ى", "ي") = ?',
                            [$normalizedAr],
                        );
                    }
                }
            })
            ->first();
    }

    private function normalizeEnglishName(string $name, string $slug): string
    {
        $name = preg_replace('/\s+/', ' ', trim(str_replace(["\n", "\r"], ' ', $name))) ?? '';

        if ($name === '') {
            $name = Str::title(str_replace(['-', '_'], ' ', $slug));
        }

        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        $name = preg_replace('/\s*-\s*/', ' - ', $name) ?? $name;

        return $name;
    }

    private function normalizeArabic(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text;
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        $text = str_replace('ى', 'ي', $text);

        return $text;
    }
}
