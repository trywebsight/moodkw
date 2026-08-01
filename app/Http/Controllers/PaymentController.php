<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Support\CheckoutErrorMessages;
use App\Services\TapPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly TapPaymentService $tapPaymentService,
    ) {}

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $hash = $request->header('hashstring', '');

        if (empty($payload)) {
            return response()->json(['error' => 'Empty payload'], 400);
        }

        $verified = $this->tapPaymentService->processWebhook($payload, $hash);

        if (! $verified) {
            return response()->json(['error' => 'Invalid signature or order not found'], 403);
        }

        return response()->json(['status' => 'ok']);
    }

    public function redirect(Request $request): RedirectResponse
    {
        $tapId = $request->query('tap_id');

        if (! $tapId) {
            Log::warning('Tap redirect missing tap_id');

            return redirect()->route('payment.failure');
        }

        $charge = $this->tapPaymentService->retrieveCharge($tapId);

        if (! $charge) {
            Log::warning('Tap redirect could not retrieve charge', ['tap_id' => $tapId]);

            return redirect()->route('payment.failure');
        }

        $order = $this->findOrderForCharge($tapId, $charge);
        $status = $charge['status'] ?? '';

        Log::info('Tap payment redirect', [
            'tap_id' => $tapId,
            'status' => $status,
            'order_id' => $order?->id,
            'response_code' => $charge['response']['code'] ?? null,
            'response_message' => $charge['response']['message'] ?? null,
        ]);

        if (! $order) {
            return redirect()->route('payment.failure');
        }

        $this->tapPaymentService->updateOrderFromTapStatus($order, $charge);

        return $this->redirectFromChargeStatus($order, $tapId, $charge, $status);
    }

    public function poll(Request $request): JsonResponse
    {
        $tapId = $request->query('tap_id');

        if (! $tapId) {
            return response()->json(['error' => 'missing_tap_id'], 400);
        }

        $charge = $this->tapPaymentService->retrieveCharge($tapId);

        if (! $charge) {
            return response()->json(['error' => 'charge_not_found'], 404);
        }

        $order = $this->findOrderForCharge($tapId, $charge);

        if (! $order) {
            return response()->json(['error' => 'order_not_found'], 404);
        }

        $status = $charge['status'] ?? '';

        $this->tapPaymentService->updateOrderFromTapStatus($order, $charge);

        if ($this->tapPaymentService->isSuccessfulStatus($status)) {
            session()->forget('pending_payment_order');
            session()->forget('checkout_draft');

            return response()->json([
                'done' => true,
                'redirect' => route('payment.success', ['order' => $order->order_number]),
            ]);
        }

        if ($this->tapPaymentService->isFailedStatus($status)) {
            return response()->json([
                'done' => true,
                'redirect' => route('payment.failure', ['order' => $order->order_number]),
            ]);
        }

        $continuationUrl = $this->tapPaymentService->chargeContinuationUrl($charge);

        if ($continuationUrl) {
            return response()->json([
                'done' => true,
                'redirect' => $continuationUrl,
            ]);
        }

        return response()->json(['done' => false, 'status' => $status]);
    }

    public function pending(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->query('order');
        $tapId = $request->query('tap_id');

        if (! $orderNumber || ! $tapId) {
            return redirect()->route('payment.failure');
        }

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return redirect()->route('payment.failure');
        }

        return view('payment.pending', compact('order', 'tapId'));
    }

    public function success(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->query('order');

        if (! $orderNumber) {
            return redirect()->route('home');
        }

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        if (! $order->isPaid() && $order->payment_method !== PaymentMethod::Cod) {
            return redirect()->route('payment.failure', ['order' => $order->order_number]);
        }

        session()->forget('pending_payment_order');
        session()->forget('checkout_draft');

        return view('payment.success', compact('order'));
    }

    public function cancelPending(): RedirectResponse
    {
        session()->forget('pending_payment_order');
        session()->forget('checkout_draft');

        return redirect()->route('home');
    }

    public function failure(Request $request): View
    {
        $orderNumber = $request->query('order') ?? session('pending_payment_order');

        $order = null;

        if ($orderNumber) {
            $order = Order::query()
                ->with(['product', 'governorate', 'area'])
                ->where('order_number', $orderNumber)
                ->first();
        }

        return view('payment.failure', compact('order'));
    }

    public function retry(Order $order): RedirectResponse
    {
        if (! $order->canRetryPayment()) {
            return redirect()->route('payment.failure', ['order' => $order->order_number])
                ->withErrors(['payment' => __('payment.cannot_retry')]);
        }

        return $this->redirectToTapPayment($order);
    }

    public function pay(Order $order): RedirectResponse
    {
        if (! $order->canRetryPayment()) {
            return redirect()->route('payment.failure', ['order' => $order->order_number])
                ->withErrors(['payment' => __('payment.cannot_retry')]);
        }

        return $this->redirectToTapPayment($order);
    }

    private function redirectFromChargeStatus(Order $order, string $tapId, array $charge, string $status): RedirectResponse
    {
        if ($this->tapPaymentService->isSuccessfulStatus($status)) {
            session()->forget('pending_payment_order');
            session()->forget('checkout_draft');

            return redirect()->route('payment.success', ['order' => $order->order_number]);
        }

        if ($this->tapPaymentService->isFailedStatus($status)) {
            return redirect()->route('payment.failure', ['order' => $order->order_number]);
        }

        $continuationUrl = $this->tapPaymentService->chargeContinuationUrl($charge);

        if ($continuationUrl) {
            return redirect()->away($continuationUrl);
        }

        if ($this->tapPaymentService->isPendingStatus($status)) {
            return redirect()->route('payment.pending', [
                'order' => $order->order_number,
                'tap_id' => $tapId,
            ]);
        }

        return redirect()->route('payment.failure', ['order' => $order->order_number]);
    }

    private function findOrderForCharge(string $tapId, array $charge): ?Order
    {
        $order = Order::query()->where('tap_charge_id', $tapId)->first();

        if ($order) {
            return $order;
        }

        $orderId = $charge['metadata']['order_id'] ?? null;

        if ($orderId) {
            return Order::query()->find($orderId);
        }

        return null;
    }

    private function redirectToTapPayment(Order $order): RedirectResponse
    {
        try {
            $charge = $this->tapPaymentService->createCharge($order, PaymentMethod::Knet);
            $redirectUrl = $charge['transaction']['url'] ?? null;

            if (! $redirectUrl) {
                throw new \RuntimeException('Payment URL not received from Tap.');
            }

            session(['pending_payment_order' => $order->order_number]);

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            Log::error('Payment initiation failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('payment.failure', ['order' => $order->order_number])
                ->withErrors(['payment' => CheckoutErrorMessages::payment($e)]);
        }
    }
}
