<?php

namespace App\Services;

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Area;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly DeliveryFeeService $deliveryFeeService,
        private readonly SettingsService $settingsService,
    ) {}

    public function calculateTotals(Product $product, int $quantity, int $governorateId): array
    {
        $unitPrice = (float) $product->price;
        $subtotal = round($unitPrice * $quantity, 3);
        $deliveryFee = $this->deliveryFeeService->getFeeForGovernorate($governorateId);
        $total = round($subtotal + $deliveryFee, 3);

        return [
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
        ];
    }

    public function createPendingOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);

            if (! $product->isAvailable($data['quantity'])) {
                throw new \RuntimeException('Product is not available in the requested quantity.');
            }

            $area = Area::query()
                ->where('id', $data['area_id'])
                ->where('governorate_id', $data['governorate_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $totals = $this->calculateTotals($product, $data['quantity'], $data['governorate_id']);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'unit_price' => $totals['unit_price'],
                'subtotal' => $totals['subtotal'],
                'delivery_fee' => $totals['delivery_fee'],
                'total' => $totals['total'],
                'governorate_id' => $data['governorate_id'],
                'area_id' => $area->id,
                'address_type' => $data['address_type'],
                'block' => $data['block'],
                'street' => $data['street'],
                'avenue' => null,
                'building' => $data['building'],
                'floor' => in_array($data['address_type'], [AddressType::Office->value, AddressType::Apartment->value], true)
                    ? ($data['floor'] ?? null)
                    : null,
                'apartment' => $data['address_type'] === AddressType::Home->value
                    ? null
                    : ($data['apartment'] ?? null),
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'payment_method' => PaymentMethod::from($data['payment_method'] ?? PaymentMethod::Knet->value),
                'payment_status' => PaymentStatus::Pending,
                'order_status' => OrderStatus::Pending,
            ]);

            $product->decrement('stock', $data['quantity']);

            return $order;
        });
    }

    public function generateOrderNumber(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd');

        do {
            $suffix = strtoupper(Str::random(5));
            $orderNumber = $prefix.'-'.$suffix;
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function cancelPendingOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $product = Product::query()->lockForUpdate()->find($order->product_id);

            if ($product) {
                $product->increment('stock', $order->quantity);
            }

            $order->delete();
        });
    }

    public function formatAmount(float $amount): string
    {
        $currency = $this->settingsService->getCurrency();

        return match ($currency) {
            'KWD', 'BHD', 'OMR', 'JOD' => number_format($amount, 3, '.', ''),
            default => number_format($amount, 2, '.', ''),
        };
    }
}
