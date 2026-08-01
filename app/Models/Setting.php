<?php

namespace App\Models;

use App\Enums\TapMode;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'tap_secret_key',
        'tap_public_key',
        'tap_mode',
        'payment_knet_enabled',
        'payment_card_enabled',
        'payment_apple_pay_enabled',
        'payment_cod_enabled',
        'store_name',
        'store_logo',
        'store_phone',
        'store_whatsapp',
        'currency',
        'seo_title',
        'seo_title_ar',
        'seo_description',
        'seo_description_ar',
        'seo_keywords',
        'seo_keywords_ar',
        'og_image',
        'working_hours_enabled',
        'working_hours',
        'timezone',
        'notification_email',
        'order_email_notifications',
        'order_sound_notifications',
        'respond_whatsapp_enabled',
        'respond_channel_api_token',
        'respond_channel_id',
        'respond_base_url',
        'respond_payment_template_name',
        'respond_payment_template_language',
        'respond_payment_template_fields',
        'respond_order_confirmation_template_name',
        'respond_order_confirmation_template_language',
        'respond_order_confirmation_template_fields',
    ];

    protected function casts(): array
    {
        return [
            'tap_mode' => TapMode::class,
            'tap_secret_key' => 'encrypted',
            'respond_channel_api_token' => 'encrypted',
            'respond_whatsapp_enabled' => 'boolean',
            'respond_payment_template_fields' => 'array',
            'respond_order_confirmation_template_fields' => 'array',
            'payment_knet_enabled' => 'boolean',
            'payment_card_enabled' => 'boolean',
            'payment_apple_pay_enabled' => 'boolean',
            'payment_cod_enabled' => 'boolean',
            'working_hours' => 'array',
            'working_hours_enabled' => 'boolean',
            'order_email_notifications' => 'boolean',
            'order_sound_notifications' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'tap_mode' => TapMode::Test,
            'currency' => 'KWD',
            'store_name' => config('app.name'),
            'timezone' => 'Asia/Kuwait',
            'working_hours_enabled' => true,
            'order_email_notifications' => true,
            'order_sound_notifications' => true,
            'payment_knet_enabled' => true,
            'payment_card_enabled' => true,
            'payment_apple_pay_enabled' => true,
            'payment_cod_enabled' => false,
        ]);
    }
}
