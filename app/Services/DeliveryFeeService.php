<?php

namespace App\Services;

use App\Models\Area;

class DeliveryFeeService
{
    public function getFeeForGovernorate(int $governorateId): float
    {
        $rate = \App\Models\DeliveryRate::query()
            ->where('governorate_id', $governorateId)
            ->where('is_active', true)
            ->first();

        return $rate ? (float) $rate->fee : 0.0;
    }

    public function getActiveGovernorates(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Governorate::query()
            ->where('is_active', true)
            ->whereHas('deliveryRate', fn ($query) => $query->where('is_active', true))
            ->whereHas('areas', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int|string>  $activeAreaIds
     */
    public function syncAreaDelivery(int $governorateId, array $activeAreaIds): void
    {
        $activeAreaIds = array_map('intval', $activeAreaIds);

        Area::query()
            ->where('governorate_id', $governorateId)
            ->update(['is_active' => false]);

        if ($activeAreaIds !== []) {
            Area::query()
                ->where('governorate_id', $governorateId)
                ->whereIn('id', $activeAreaIds)
                ->update(['is_active' => true]);
        }
    }

    /**
     * @return list<int>
     */
    public function activeAreaIdsForGovernorate(int $governorateId): array
    {
        return Area::query()
            ->where('governorate_id', $governorateId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('id')
            ->all();
    }
}
