<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('payment_knet_enabled')->default(true)->after('tap_mode');
            $table->boolean('payment_card_enabled')->default(true)->after('payment_knet_enabled');
            $table->boolean('payment_apple_pay_enabled')->default(true)->after('payment_card_enabled');
            $table->boolean('payment_cod_enabled')->default(false)->after('payment_apple_pay_enabled');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('delivery_notes');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_knet_enabled',
                'payment_card_enabled',
                'payment_apple_pay_enabled',
                'payment_cod_enabled',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
