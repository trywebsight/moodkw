<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapPaymentService
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly OrderService $orderService,
    ) {}

    private function apiBase(): string
    {
        return config('tap.api_base', 'https://api.tap.company/v2');
    }

    public function createCharge(Order $order, PaymentMethod $method, ?string $tokenId = null): array
    {
        $secretKey = $this->settingsService->getTapSecretKey();

        if (! $secretKey) {
            throw new \RuntimeException('Tap secret key is not configured.');
        }

        if ($method->requiresToken() && ! $tokenId) {
            throw new \RuntimeException('Payment token is required for this method.');
        }

        $currency = $this->settingsService->getCurrency();
        $amount = $this->formatTapAmount((float) $order->total, $currency);

        $phone = $this->normalizePhone($order->customer_phone);

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'customer_initiated' => true,
            'threeDSecure' => $method !== PaymentMethod::Knet,
            'save_card' => false,
            'description' => "Order {$order->order_number}",
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $method->value,
            ],
            'reference' => [
                'transaction' => $order->order_number,
                'order' => $order->order_number,
            ],
            'customer' => [
                'first_name' => $order->customer_name,
                'phone' => [
                    'country_code' => '965',
                    'number' => $phone,
                ],
            ],
            'source' => [
                'id' => $method->tapSourceId($tokenId),
            ],
            'post' => [
                'url' => route('payment.webhook'),
            ],
            'redirect' => [
                'url' => route('payment.redirect'),
            ],
        ];

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->post($this->apiBase().'/charges', $payload);

        if (! $response->successful()) {
            Log::error('Tap charge creation failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to create payment. Please try again.');
        }

        $data = $response->json();

        $order->update([
            'tap_charge_id' => $data['id'] ?? null,
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'tap_charge_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'INITIATED',
            'amount' => $order->total,
            'currency' => $currency,
            'raw_payload' => $data,
        ]);

        return $data;
    }

    public function retrieveCharge(string $chargeId): ?array
    {
        $secretKey = $this->settingsService->getTapSecretKey();

        if (! $secretKey) {
            return null;
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get($this->apiBase().'/charges/'.$chargeId);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function verifyWebhookHash(array $payload, string $receivedHash): bool
    {
        $secretKey = $this->settingsService->getTapSecretKey();

        if (! $secretKey || ! $receivedHash) {
            return false;
        }

        $currency = $this->settingsService->getCurrency();
        $amount = $this->formatTapAmount((float) ($payload['amount'] ?? 0), $currency);

        $id = $payload['id'] ?? '';
        $gatewayReference = $payload['reference']['gateway'] ?? '';
        $paymentReference = $payload['reference']['payment'] ?? '';
        $status = $payload['status'] ?? '';
        $created = $payload['transaction']['created'] ?? '';

        $toBeHashed = 'x_id'.$id
            .'x_amount'.$amount
            .'x_currency'.($payload['currency'] ?? $currency)
            .'x_gateway_reference'.$gatewayReference
            .'x_payment_reference'.$paymentReference
            .'x_status'.$status
            .'x_created'.$created;

        $computedHash = hash_hmac('sha256', $toBeHashed, $secretKey);

        return hash_equals($computedHash, $receivedHash);
    }

    public function processWebhook(array $payload, string $receivedHash): bool
    {
        if (! $this->verifyWebhookHash($payload, $receivedHash)) {
            Log::warning('Tap webhook hash verification failed', ['charge_id' => $payload['id'] ?? null]);

            return false;
        }

        $chargeId = $payload['id'] ?? null;

        if (! $chargeId) {
            return false;
        }

        $order = Order::query()->where('tap_charge_id', $chargeId)->first();

        if (! $order) {
            $orderId = $payload['metadata']['order_id'] ?? null;
            if ($orderId) {
                $order = Order::query()->find($orderId);
            }
        }

        if (! $order) {
            Log::warning('Tap webhook: order not found', ['charge_id' => $chargeId]);

            return false;
        }

        $this->updateOrderFromTapStatus($order, $payload);

        return true;
    }

    public function updateOrderFromTapStatus(Order $order, array $payload): void
    {
        $tapStatus = strtoupper($payload['status'] ?? '');

        PaymentTransaction::create([
            'order_id' => $order->id,
            'tap_charge_id' => $payload['id'] ?? $order->tap_charge_id,
            'status' => $tapStatus,
            'amount' => $order->total,
            'currency' => $payload['currency'] ?? $this->settingsService->getCurrency(),
            'raw_payload' => $payload,
            'paid_at' => $this->isSuccessfulStatus($tapStatus) ? now() : null,
        ]);

        if ($this->isSuccessfulStatus($tapStatus)) {
            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'tap_charge_id' => $payload['id'] ?? $order->tap_charge_id,
                'paid_at' => now(),
            ]);

            $this->sendOrderConfirmationWhatsApp($order);
        } elseif ($this->isFailedStatus($tapStatus)) {
            $order->update([
                'payment_status' => PaymentStatus::Failed,
            ]);
        }
    }

    public function isSuccessfulStatus(string $status): bool
    {
        return in_array(strtoupper($status), ['CAPTURED', 'AUTHORIZED'], true);
    }

    public function isFailedStatus(string $status): bool
    {
        return in_array(strtoupper($status), [
            'ABANDONED', 'CANCELLED', 'FAILED', 'DECLINED', 'RESTRICTED', 'VOID', 'TIMEDOUT', 'UNKNOWN',
        ], true);
    }

    public function isPendingStatus(string $status): bool
    {
        return in_array(strtoupper($status), [
            'INITIATED', 'PENDING', 'IN_PROGRESS', 'PROCESSING',
        ], true);
    }

    public function chargeContinuationUrl(array $charge): ?string
    {
        $url = $charge['transaction']['url'] ?? null;

        if (! is_string($url) || $url === '') {
            return null;
        }

        return $url;
    }

    public function formatTapAmount(float $amount, string $currency): string
    {
        $decimals = match ($currency) {
            'KWD', 'BHD', 'OMR', 'JOD' => 3,
            default => 2,
        };

        return number_format($amount, $decimals, '.', '');
    }

    private function sendOrderConfirmationWhatsApp(Order $order): void
    {
        $respondService = app(RespondService::class);

        if (! $respondService->isEnabled()) {
            return;
        }

        try {
            $response = $respondService->sendOrderConfirmation($order);

            if (($response['success'] ?? false) !== true && ! ($response['skipped'] ?? false)) {
                Log::channel('respond')->warning('Order confirmation WhatsApp failed', [
                    'order_id' => $order->id,
                    'message' => $response['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Throwable $exception) {
            Log::channel('respond')->error('Order confirmation WhatsApp exception', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '965')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }
}
