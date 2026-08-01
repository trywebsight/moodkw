<?php

namespace App\Filament\Resources\DeliveryRates\Schemas;

use App\Models\Area;
use App\Models\DeliveryRate;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DeliveryRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery fee')
                    ->schema([
                        Select::make('governorate_id')
                            ->relationship(
                                name: 'governorate',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name_ar
                                ? "{$record->name} — {$record->name_ar}"
                                : $record->name)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true)
                            ->live()
                            ->afterStateUpdated(function (?int $state, Set $set): void {
                                if (! $state) {
                                    $set('active_area_ids', []);

                                    return;
                                }

                                $set(
                                    'active_area_ids',
                                    Area::query()
                                        ->where('governorate_id', $state)
                                        ->orderBy('name')
                                        ->pluck('id')
                                        ->all(),
                                );
                            }),
                        TextInput::make('fee')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.001)
                            ->prefix('KWD'),
                        Toggle::make('is_active')
                            ->label('Governorate delivery active')
                            ->default(true)
                            ->helperText('Turn off to disable delivery for the whole governorate.'),
                    ])
                    ->columns(2),

                Section::make('Delivery areas')
                    ->description('All areas are enabled by default. Uncheck any area that should not accept delivery.')
                    ->schema([
                        CheckboxList::make('active_area_ids')
                            ->label('Areas')
                            ->options(function (Get $get, ?DeliveryRate $record): array {
                                $governorateId = $record?->governorate_id ?? $get('governorate_id');

                                if (! $governorateId) {
                                    return [];
                                }

                                return Area::query()
                                    ->where('governorate_id', $governorateId)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Area $area): array => [
                                        $area->id => $area->name_ar
                                            ? "{$area->name} — {$area->name_ar}"
                                            : $area->name,
                                    ])
                                    ->all();
                            })
                            ->columns(2)
                            ->bulkToggleable()
                            ->searchable()
                            ->visible(fn (Get $get, ?DeliveryRate $record): bool => filled($record?->governorate_id ?? $get('governorate_id')))
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
