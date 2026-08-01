<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->text('tap_secret_key')->nullable();
            $table->string('tap_public_key')->nullable();
            $table->string('tap_mode')->default('test');
            $table->string('store_name')->nullable();
            $table->string('store_phone')->nullable();
            $table->string('store_whatsapp')->nullable();
            $table->string('currency', 3)->default('KWD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
