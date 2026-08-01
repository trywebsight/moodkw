<?php

namespace App\Support;

use Throwable;

class CheckoutErrorMessages
{
    public static function payment(Throwable $throwable): string
    {
        return __('checkout.payment_unavailable');
    }
}
