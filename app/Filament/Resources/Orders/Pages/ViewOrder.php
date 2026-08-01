<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\RespondService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_payment_link')
                ->label('Copy payment link')
                ->icon('heroicon-o-link')
                ->visible(fn (): bool => $this->record->canRetryPayment())
                ->action(function (): void {
                    $url = $this->record->signedPaymentUrl();

                    if (! $url) {
                        Notification::make()
                            ->title('Payment link unavailable')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->js('navigator.clipboard.writeText('.json_encode($url).')');

                    Notification::make()
                        ->title('Payment link copied')
                        ->success()
                        ->send();
                }),
            Action::make('whatsapp_payment_api')
                ->label('Send via WhatsApp')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => $this->record->canRetryPayment()
                    && app(RespondService::class)->isEnabled())
                ->requiresConfirmation()
                ->modalDescription('Send the configured payment link template to the customer via Respond.io?')
                ->action(function (): void {
                    $response = app(RespondService::class)->sendPaymentLink($this->record);

                    if ($response['success'] ?? false) {
                        Notification::make()
                            ->title('WhatsApp sent')
                            ->body($response['message'] ?? 'Payment link sent via Respond.io.')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('WhatsApp failed')
                        ->body($response['message'] ?? 'Could not send via Respond.io.')
                        ->danger()
                        ->send();
                }),
            Action::make('whatsapp_payment_link')
                ->label('Open in WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn (): bool => $this->record->canRetryPayment() && $this->record->whatsappPaymentUrl() !== null)
                ->url(fn (): string => $this->record->whatsappPaymentUrl())
                ->openUrlInNewTab(),
            Action::make('download_invoice')
                ->label('Download Invoice')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (): string => route('invoices.download', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
