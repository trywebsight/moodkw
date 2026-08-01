<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\OrderStatus;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Support\CheckoutErrorMessages;
use App\Support\TapPaymentToken;
use App\Services\OrderService;
use App\Services\SettingsService;
use App\Services\TapPaymentService;
use App\Services\WorkingHoursService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly TapPaymentService $tapPaymentService,
        private readonly WorkingHoursService $workingHoursService,
    ) {}

    public function store(CheckoutRequest $request): RedirectResponse
    {
        if (! $this->workingHoursService->isOpen()) {
            return back()->withErrors([
                'checkout' => __('checkout.store_closed'),
            ])->withInput();
        }

        $product = Product::query()->where('is_active', true)->first();

        if (! $product) {
            return back()->withErrors(['product' => __('checkout.no_product')])->withInput();
        }

        if (! $product->isAvailable($request->integer('quantity'))) {
            return back()->withErrors(['quantity' => 'Insufficient stock for the requested quantity.'])->withInput();
        }

        $paymentMethod = PaymentMethod::from($request->string('payment_method')->toString());

        if (! app(SettingsService::class)->isPaymentMethodEnabled($paymentMethod)) {
            return back()
                ->withErrors(['payment_method' => __('checkout.payment_method_disabled')])
                ->withInput();
        }

        $tokenId = TapPaymentToken::extractTokenId($request->input('payment_token'));

        if ($paymentMethod->requiresToken() && ! $tokenId) {
            return back()
                ->withErrors(['payment_method' => __('checkout.payment_token_required')])
                ->withInput();
        }

        try {
            $order = $this->orderService->createPendingOrder(array_merge(
                $request->validated(),
                ['product_id' => $product->id],
            ));

            session()->forget('checkout_draft');

            if ($paymentMethod === PaymentMethod::Cod) {
                $order->update(['order_status' => OrderStatus::Confirmed]);

                session()->forget('pending_payment_order');

                return redirect()
                    ->route('payment.success', ['order' => $order->order_number])
                    ->with('cod_order', true);
            }
        } catch (\Throwable $e) {
            Log::error('Checkout order creation failed', [
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['checkout' => __('checkout.payment_unavailable')])->withInput();
        }

        try {
            $charge = $this->tapPaymentService->createCharge($order, $paymentMethod, $tokenId);

            return $this->completeCheckoutCharge($order, $charge);
        } catch (\Throwable $e) {
            Log::error('Checkout payment initiation failed', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod->value,
                'message' => $e->getMessage(),
            ]);

            session(['pending_payment_order' => $order->order_number]);

            return redirect()
                ->route('payment.failure', ['order' => $order->order_number])
                ->withErrors(['payment' => CheckoutErrorMessages::payment($e)]);
        }
    }

    private function completeCheckoutCharge(Order $order, array $charge): RedirectResponse
    {
        $status = $charge['status'] ?? '';

        $this->tapPaymentService->updateOrderFromTapStatus($order, $charge);

        if ($this->tapPaymentService->isSuccessfulStatus($status)) {
            session()->forget('pending_payment_order');
            session()->forget('checkout_draft');

            return redirect()->route('payment.success', ['order' => $order->order_number]);
        }

        $redirectUrl = $this->tapPaymentService->chargeContinuationUrl($charge);

        if ($redirectUrl) {
            session(['pending_payment_order' => $order->order_number]);

            return redirect()->away($redirectUrl);
        }

        session(['pending_payment_order' => $order->order_number]);

        return redirect()
            ->route('payment.failure', ['order' => $order->order_number])
            ->withErrors(['payment' => __('checkout.payment_unavailable')]);
    }
}
