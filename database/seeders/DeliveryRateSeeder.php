<?php

namespace Database\Seeders;

use App\Models\DeliveryRate;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

class DeliveryRateSeeder extends Seeder
{
    public function run(): void
    {
        $fees = [
            'Capital' => 1.500,
            'Hawalli' => 1.500,
            'Farwaniya' => 2.000,
            'Mubarak Al-Kabeer' => 2.500,
            'Ahmadi' => 2.500,
            'Jahra' => 3.000,
        ];

        Governorate::query()->each(function (Governorate $governorate) use ($fees) {
            DeliveryRate::query()->updateOrCreate(
                ['governorate_id' => $governorate->id],
                [
                    'fee' => $fees[$governorate->name] ?? 2.000,
                    'is_active' => true,
                ],
            );
        });
    }
}
