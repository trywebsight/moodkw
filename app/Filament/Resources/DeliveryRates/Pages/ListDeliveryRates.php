<?php

namespace App\Filament\Resources\DeliveryRates\Pages;

use App\Filament\Resources\DeliveryRates\DeliveryRateResource;
use App\Models\DeliveryRate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDeliveryRates extends ListRecords
{
    protected static string $resource = DeliveryRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setFeeForAll')
                ->label('Set fee for all')
                ->icon(Heroicon::OutlinedBanknotes)
                ->modalHeading('Set delivery fee for all governorates')
                ->modalDescription('This will apply the same delivery fee to every governorate.')
                ->schema([
                    TextInput::make('fee')
                        ->label('Delivery fee')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.001)
                        ->prefix('KWD'),
                ])
                ->action(function (array $data): void {
                    $updated = DeliveryRate::query()->update(['fee' => $data['fee']]);

                    Notification::make()
                        ->title('Delivery fees updated')
                        ->body("Updated {$updated} governorate".($updated === 1 ? '' : 's').' to KWD '.number_format((float) $data['fee'], 3))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
