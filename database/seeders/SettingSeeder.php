<?php

namespace Database\Seeders;

use App\Enums\TapMode;
use App\Models\Setting;
use App\Services\WorkingHoursService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $tapMode = env('TAP_MODE', 'test') === 'live' ? TapMode::Live : TapMode::Test;
        $tapSecretKey = env('TAP_SECRET_KEY');
        $tapPublicKey = env('TAP_PUBLIC_KEY');

        Setting::query()->updateOrCreate([], [
            'tap_secret_key' => $tapSecretKey,
            'tap_public_key' => $tapPublicKey,
            'tap_mode' => $tapMode,
            'store_name' => 'MOOD',
            'store_logo' => 'store/mood-logo.jpg',
            'store_phone' => '+965 5058 7086',
            'store_whatsapp' => '+96550587086',
            'currency' => 'KWD',
            'seo_title' => 'MOOD — Order Sweet Truffles Online',
            'seo_title_ar' => 'مود — اطلب ترافل حلو أونلاين',
            'seo_description' => 'Handcrafted cocoa truffle bites from MOOD. Order online with delivery across Kuwait.',
            'seo_description_ar' => 'ترافل مغطى بالكاكاو من مود. اطلب أونلاين مع توصيل في جميع أنحاء الكويت.',
            'seo_keywords' => 'mood, mood kuwait, mood sweet, truffles, chocolate, kuwait sweets, mood.sweet.kw',
            'seo_keywords_ar' => 'مود, مود الكويت, ترافل, شوكولاتة, حلويات الكويت',
            'og_image' => 'seo/mood-og.jpg',
            'working_hours_enabled' => true,
            'working_hours' => app(WorkingHoursService::class)->defaultWorkingHours(),
            'timezone' => 'Asia/Kuwait',
            'notification_email' => env('MAIL_FROM_ADDRESS', 'admin@example.com'),
            'order_email_notifications' => true,
            'order_sound_notifications' => true,
            'payment_knet_enabled' => true,
            'payment_card_enabled' => true,
            'payment_apple_pay_enabled' => true,
            'payment_cod_enabled' => false,
        ]);
    }
}
