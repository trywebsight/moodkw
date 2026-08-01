<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_totals_are_calculated_on_server(): void
    {
        $this->seed();

        $product = Product::query()->first();
        $governorateId = 1;

        $orderService = app(OrderService::class);
        $totals = $orderService->calculateTotals($product, 2, $governorateId);

        $this->assertEquals(round((float) $product->price * 2, 3), $totals['subtotal']);
        $this->assertGreaterThan(0, $totals['delivery_fee']);
        $this->assertEquals(round($totals['subtotal'] + $totals['delivery_fee'], 3), $totals['total']);
    }
}
