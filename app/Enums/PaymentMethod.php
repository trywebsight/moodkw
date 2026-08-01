<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Knet = 'knet';
    case Card = 'card';
    case ApplePay = 'apple_pay';
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Knet => __('checkout.payment_knet'),
            self::Card => __('checkout.payment_card'),
            self::ApplePay => __('checkout.payment_apple_pay'),
            self::Cod => __('checkout.payment_cod'),
        };
    }

    public function tapSourceId(?string $tokenId = null): string
    {
        return match ($this) {
            self::Knet => 'src_kw.knet',
            self::Card, self::ApplePay => $tokenId ?? '',
            self::Cod => '',
        };
    }

    public function requiresToken(): bool
    {
        return in_array($this, [self::Card, self::ApplePay], true);
    }

    public function isOnline(): bool
    {
        return $this !== self::Cod;
    }
}
