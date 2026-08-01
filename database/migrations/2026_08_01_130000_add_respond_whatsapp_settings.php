<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('respond_whatsapp_enabled')->default(false)->after('order_sound_notifications');
            $table->text('respond_channel_api_token')->nullable()->after('respond_whatsapp_enabled');
            $table->string('respond_channel_id')->nullable()->after('respond_channel_api_token');
            $table->string('respond_base_url')->nullable()->after('respond_channel_id');
            $table->string('respond_payment_template_name')->nullable()->after('respond_base_url');
            $table->string('respond_payment_template_language', 10)->nullable()->after('respond_payment_template_name');
            $table->json('respond_payment_template_fields')->nullable()->after('respond_payment_template_language');
            $table->string('respond_order_confirmation_template_name')->nullable()->after('respond_payment_template_fields');
            $table->string('respond_order_confirmation_template_language', 10)->nullable()->after('respond_order_confirmation_template_name');
            $table->json('respond_order_confirmation_template_fields')->nullable()->after('respond_order_confirmation_template_language');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
