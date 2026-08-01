<?php

namespace App\Support;

use App\Enums\AddressType;
use Illuminate\Validation\Rule;

class CheckoutAddressRules
{
    public static function rules(?string $addressType = null): array
    {
        $type = $addressType ?? request()->input('address_type', AddressType::Home->value);

        $needsFloor = in_array($type, [AddressType::Office->value, AddressType::Apartment->value], true);

        $apartmentRules = match ($type) {
            AddressType::Home->value => ['nullable', 'prohibited'],
            AddressType::Apartment->value => ['required', 'string', 'max:20'],
            AddressType::Office->value => ['nullable', 'string', 'max:20'],
            default => ['nullable', 'string', 'max:20'],
        };

        return [
            'address_type' => ['required', Rule::enum(AddressType::class)],
            'governorate_id' => ['required', 'integer'],
            'area_id' => ['required', 'integer'],
            'block' => ['required', 'string', 'max:20'],
            'street' => ['required', 'string', 'max:50'],
            'building' => ['required', 'string', 'max:20'],
            'floor' => $needsFloor
                ? ['required', 'string', 'max:20']
                : ['nullable', 'prohibited'],
            'apartment' => $apartmentRules,
            'delivery_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
