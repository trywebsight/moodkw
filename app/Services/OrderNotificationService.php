<?php

namespace App\Services;

use App\Mail\OrderReceivedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    public function notifyNewOrder(Order $order): void
    {
        $setting = app(SettingsService::class)->get();

        if ($setting->order_email_notifications) {
            $this->sendEmail($order, $setting);
        }

        $admins = \App\Models\User::query()->get();

        if ($admins->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewOrderNotification($order));
        }
    }

    public function sendTestEmail(string $email): void
    {
        $order = Order::query()->with(['product', 'governorate', 'area'])->latest()->first();

        if ($order) {
            Mail::to($email)->send(new OrderReceivedMail($order));

            return;
        }

        Mail::raw(__('mail.test_body'), function ($message) use ($email): void {
            $message->to($email)->subject(__('mail.test_subject'));
        });
    }

    private function sendEmail(Order $order, \App\Models\Setting $setting): void
    {
        $email = $setting->notification_email ?? config('mail.from.address');

        if (! $email) {
            Log::warning('Order email notification skipped: no notification_email configured.');

            return;
        }

        try {
            Mail::to($email)->send(new OrderReceivedMail($order));
        } catch (\Throwable $exception) {
            Log::error('Order email notification failed', [
                'order_id' => $order->id,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
