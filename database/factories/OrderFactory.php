<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $product = Product::query()->first() ?? Product::factory()->create();
        $governorate = Governorate::query()->inRandomOrder()->first();
        $area = Area::query()->where('governorate_id', $governorate->id)->inRandomOrder()->first();

        $quantity = fake()->numberBetween(1, 3);
        $orderService = app(OrderService::class);
        $totals = $orderService->calculateTotals($product, $quantity, $governorate->id);
        $addressType = fake()->randomElement(AddressType::cases());

        return [
            'order_number' => $orderService->generateOrderNumber(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->randomElement(['2', '4', '5', '6', '9']).fake()->numerify('#######'),
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $totals['unit_price'],
            'subtotal' => $totals['subtotal'],
            'delivery_fee' => $totals['delivery_fee'],
            'total' => $totals['total'],
            'governorate_id' => $governorate->id,
            'area_id' => $area->id,
            'address_type' => $addressType,
            'block' => (string) fake()->numberBetween(1, 12),
            'street' => (string) fake()->numberBetween(1, 50),
            'avenue' => null,
            'building' => (string) fake()->numberBetween(1, 100),
            'floor' => $addressType === AddressType::Office || $addressType === AddressType::Apartment
                ? (string) fake()->numberBetween(1, 10)
                : null,
            'apartment' => $addressType === AddressType::Home
                ? null
                : (string) fake()->numberBetween(1, 20),
            'delivery_notes' => fake()->optional()->sentence(),
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::Pending,
        ];
    }
}
