<?php

namespace App\Filament\Resources\DeliveryRates\Pages;

use App\Filament\Resources\DeliveryRates\Concerns\SyncsDeliveryAreas;
use App\Filament\Resources\DeliveryRates\DeliveryRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryRate extends EditRecord
{
    use SyncsDeliveryAreas;

    protected static string $resource = DeliveryRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['active_area_ids'] = $this->fillActiveAreaIds($this->record->governorate_id);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncDeliveryAreasFromForm($this->record->governorate_id);
    }
}
