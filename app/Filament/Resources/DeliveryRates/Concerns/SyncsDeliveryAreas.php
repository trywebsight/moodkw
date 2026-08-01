<?php

namespace App\Filament\Resources\DeliveryRates\Concerns;

use App\Models\Area;
use App\Services\DeliveryFeeService;

trait SyncsDeliveryAreas
{
    protected function fillActiveAreaIds(int $governorateId): array
    {
        return app(DeliveryFeeService::class)->activeAreaIdsForGovernorate($governorateId);
    }

    protected function syncDeliveryAreasFromForm(int $governorateId): void
    {
        $state = $this->form->getRawState();
        $activeAreaIds = $state['active_area_ids'] ?? [];

        if ($activeAreaIds === []) {
            $activeAreaIds = Area::query()
                ->where('governorate_id', $governorateId)
                ->pluck('id')
                ->all();
        }

        app(DeliveryFeeService::class)->syncAreaDelivery($governorateId, $activeAreaIds);
    }
}
