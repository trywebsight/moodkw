<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderNotificationService;

class OrderObserver
{
    public function __construct(
        private readonly OrderNotificationService $notificationService,
    ) {}

    public function created(Order $order): void
    {
        $this->notificationService->notifyNewOrder($order);
    }
}
