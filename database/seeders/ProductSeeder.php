<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            'products/mood-box.png',
            'products/mood-bites-cross-section.png',
            'products/mood-bites-stacked.png',
            'products/mood-hand-holding.png',
            'products/mood-truffles-stacked.png',
            'products/mood-truffles-pedestal.png',
            'products/mood-truffles-closeup.png',
        ];

        Product::query()->updateOrCreate(
            ['name' => 'MOOD Truffle Box'],
            [
                'name_ar' => 'علبة ترافل مود',
                'description' => 'Our signature cocoa-coated truffle bites, packed in the iconic MOOD box. Crafted with care and delivered fresh across Kuwait.',
                'description_ar' => 'ترافل مود المميز مغطى بالكاكاو، في علبتنا الأيقونية. مصنوع بعناية ويُوصَل طازجاً في جميع أنحاء الكويت.',
                'price' => 10.000,
                'image' => $images[0],
                'images' => $images,
                'is_active' => true,
                'stock' => 100,
            ],
        );
    }
}
