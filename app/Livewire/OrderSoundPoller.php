<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Setting;
use Livewire\Component;

class OrderSoundPoller extends Component
{
    public int $lastOrderId = 0;

    public function mount(): void
    {
        $this->lastOrderId = (int) Order::query()->max('id');
    }

    public function checkNewOrders(): void
    {
        if (! Setting::current()->order_sound_notifications) {
            return;
        }

        $latestId = (int) Order::query()->max('id');

        if ($latestId > $this->lastOrderId) {
            $this->dispatch('play-order-sound');
            $this->lastOrderId = $latestId;
        }
    }

    public function render()
    {
        return view('filament.hooks.order-sound');
    }
}
