<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('store_logo')->nullable()->after('store_name');
            $table->string('seo_title')->nullable();
            $table->string('seo_title_ar')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_description_ar')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('seo_keywords_ar')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('working_hours_enabled')->default(true);
            $table->json('working_hours')->nullable();
            $table->string('timezone')->default('Asia/Kuwait');
            $table->string('notification_email')->nullable();
            $table->boolean('order_email_notifications')->default(true);
            $table->boolean('order_sound_notifications')->default(true);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->text('description_ar')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'store_logo',
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
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'description_ar']);
        });
    }
};
