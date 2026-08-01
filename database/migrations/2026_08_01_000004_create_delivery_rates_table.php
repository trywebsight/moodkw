<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->decimal('fee', 10, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('governorate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_rates');
    }
};
