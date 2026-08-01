<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 3);
            $table->decimal('subtotal', 10, 3);
            $table->decimal('delivery_fee', 10, 3)->default(0);
            $table->decimal('total', 10, 3);
            $table->foreignId('governorate_id')->constrained();
            $table->foreignId('area_id')->constrained();
            $table->string('block');
            $table->string('street');
            $table->string('avenue')->nullable();
            $table->string('building');
            $table->string('floor')->nullable();
            $table->string('apartment')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('order_status')->default('pending');
            $table->string('tap_charge_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['payment_status', 'created_at']);
            $table->index(['order_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
