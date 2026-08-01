<?php

namespace App\Support;

class CheckoutValidation
{
    public static function messages(): array
    {
        return [
            'customer_name.required' => __('checkout.validation.customer_name_required'),
            'customer_phone.required' => __('checkout.validation.customer_phone_required'),
            'customer_phone.regex' => __('checkout.phone_invalid'),
            'quantity.required' => __('checkout.validation.quantity_required'),
            'governorate_id.required' => __('checkout.validation.governorate_id_required'),
            'area_id.required' => __('checkout.validation.area_id_required'),
            'address_type.required' => __('checkout.validation.address_type_required'),
            'block.required' => __('checkout.validation.block_required'),
            'street.required' => __('checkout.validation.street_required'),
            'building.required' => __('checkout.validation.house_number_required'),
            'floor.required' => __('checkout.validation.floor_required'),
            'floor.prohibited' => __('checkout.validation.floor_prohibited'),
            'apartment.required' => __('checkout.validation.apartment_required'),
            'payment_method.required' => __('checkout.payment_select_method'),
            'payment_token.required' => __('checkout.payment_token_required'),
        ];
    }

    public static function attributes(): array
    {
        return [
            'customer_name' => __('checkout.full_name'),
            'customer_phone' => __('checkout.mobile_number'),
            'address_type' => __('checkout.address_type'),
            'governorate_id' => __('checkout.governorate'),
            'area_id' => __('checkout.area'),
            'block' => __('checkout.block'),
            'street' => __('checkout.street'),
            'building' => __('checkout.house_number'),
            'avenue' => __('checkout.avenue'),
            'floor' => __('checkout.floor'),
            'apartment' => __('checkout.apartment'),
            'delivery_notes' => __('checkout.delivery_notes'),
            'quantity' => __('checkout.quantity'),
            'payment_method' => __('checkout.payment_method'),
            'payment_token' => __('checkout.payment_method'),
        ];
    }
}
