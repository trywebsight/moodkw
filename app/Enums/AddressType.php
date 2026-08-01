<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AddressType: string implements HasLabel
{
    case Home = 'home';
    case Apartment = 'apartment';
    case Office = 'office';

    public function label(): string
    {
        return match ($this) {
            self::Home => __('checkout.address_type_home'),
            self::Apartment => __('checkout.address_type_apartment'),
            self::Office => __('checkout.address_type_office'),
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }
}
