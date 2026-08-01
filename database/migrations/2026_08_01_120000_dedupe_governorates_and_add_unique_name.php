<?php

use App\Models\Area;
use App\Models\DeliveryRate;
use App\Models\Governorate;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $names = Governorate::query()
            ->select('name')
            ->groupBy('name')
            ->pluck('name');

        foreach ($names as $name) {
            $keep = Governorate::query()
                ->where('name', $name)
                ->orderBy('id')
                ->first();

            if (! $keep) {
                continue;
            }

            $duplicates = Governorate::query()
                ->where('name', $name)
                ->where('id', '!=', $keep->id)
                ->get();

            foreach ($duplicates as $duplicate) {
                Order::query()
                    ->where('governorate_id', $duplicate->id)
                    ->update(['governorate_id' => $keep->id]);

                Area::query()
                    ->where('governorate_id', $duplicate->id)
                    ->delete();

                DeliveryRate::query()
                    ->where('governorate_id', $duplicate->id)
                    ->delete();

                $duplicate->delete();
            }
        }

        Schema::table('governorates', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
