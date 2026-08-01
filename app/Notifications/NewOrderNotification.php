<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\SettingsService;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $currency = app(SettingsService::class)->getCurrency();

        return FilamentNotification::make()
            ->title(__('mail.new_order_title'))
            ->body(__('mail.new_order_body', [
                'number' => $this->order->order_number,
                'customer' => $this->order->customer_name,
                'total' => number_format((float) $this->order->total, 3).' '.$currency,
            ]))
            ->icon('heroicon-o-shopping-bag')
            ->success()
            ->getDatabaseMessage();
    }
}
