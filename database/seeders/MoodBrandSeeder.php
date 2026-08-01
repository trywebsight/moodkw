<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MoodBrandSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLogo();
        $this->seedSettings();
        $this->seedProduct();
    }

    private function seedLogo(): void
    {
        $destinationDir = storage_path('app/public/store');

        if (! File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true);
        }

        $jpgSource = public_path('images/mood-logo.jpg');
        $jpgDestination = $destinationDir.'/mood-logo.jpg';

        if (File::exists($jpgSource)) {
            File::copy($jpgSource, $jpgDestination);
        }
    }

    private function seedSettings(): void
    {
        $setting = Setting::current();

        $setting->update([
            'store_name' => 'MOOD',
            'store_logo' => 'store/mood-logo.jpg',
            'store_phone' => '+965 5058 7086',
            'store_whatsapp' => '+96550587086',
            'seo_title' => 'MOOD — Order Sweet Truffles Online',
            'seo_title_ar' => 'مود — اطلب ترافل حلو أونلاين',
            'seo_description' => 'Handcrafted cocoa truffle bites from MOOD. Order online with delivery across Kuwait.',
            'seo_description_ar' => 'ترافل مغطى بالكاكاو من مود. اطلب أونلاين مع توصيل في جميع أنحاء الكويت.',
            'seo_keywords' => 'mood, mood kuwait, mood sweet, truffles, chocolate, kuwait sweets, mood.sweet.kw',
            'seo_keywords_ar' => 'مود, مود الكويت, ترافل, شوكولاتة, حلويات الكويت',
            'og_image' => 'seo/mood-og.jpg',
        ]);
    }

    private function seedProduct(): void
    {
        $product = Product::query()->first();

        $attributes = [
            'name' => 'MOOD Truffle Box',
            'name_ar' => 'علبة ترافل مود',
            'description' => 'Our signature cocoa-coated truffle bites, packed in the iconic MOOD box. Crafted with care and delivered fresh across Kuwait.',
            'description_ar' => 'ترافل مود المميز مغطى بالكاكاو، في علبتنا الأيقونية. مصنوع بعناية ويُوصَل طازجاً في جميع أنحاء الكويت.',
            'price' => 10.000,
            'is_active' => true,
            'stock' => 100,
        ];

        if ($product) {
            $product->update($attributes);
        } else {
            Product::query()->create($attributes);
        }
    }
}
