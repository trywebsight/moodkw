<?php

namespace App\Filament\Resources\DeliveryRates\Pages;

use App\Filament\Resources\DeliveryRates\Concerns\SyncsDeliveryAreas;
use App\Filament\Resources\DeliveryRates\DeliveryRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryRate extends CreateRecord
{
    use SyncsDeliveryAreas;

    protected static string $resource = DeliveryRateResource::class;

    protected function afterCreate(): void
    {
        $this->syncDeliveryAreasFromForm($this->record->governorate_id);
    }
}
