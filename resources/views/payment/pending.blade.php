@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    @php
        $settings = \App\Models\Setting::current();
        $storeName = $settings->store_name ?? config('app.name');
        $storeLogo = $settings->store_logo ? asset('storage/'.$settings->store_logo) : null;
    @endphp
    <x-menu-header :name="$storeName" :logo="$storeLogo" />

    <main class="menu-wrapper">
        <div class="menu-content menu-content--checkout">
            <div class="payment-status-screen payment-status-screen--pending" id="payment-pending"
                data-poll-url="{{ route('payment.poll', ['tap_id' => $tapId]) }}"
                data-failure-url="{{ route('payment.failure', ['order' => $order->order_number]) }}">
                <header class="payment-status-header">
                    <div class="payment-status-spinner" aria-hidden="true"></div>
                    <div class="payment-status-heading">
                        <h1 class="bg-title bg-title--compact payment-status-title">{{ __('payment.pending_title') }}</h1>
                        <p class="payment-status-lead">{{ __('payment.pending_hint') }}</p>
                    </div>
                </header>

                <p class="payment-order-ref">
                    {{ __('payment.order_number') }}
                    <span class="menu-value-ltr">{{ $order->order_number }}</span>
                </p>

                <p class="payment-status-followup" id="payment-pending-timeout" hidden>
                    {{ __('payment.pending_timeout') }}
                    <a href="{{ route('payment.failure', ['order' => $order->order_number]) }}">{{ __('payment.pending_check_status') }}</a>
                </p>
            </div>
        </div>
    </main>
</div>

<script>
(function () {
    const root = document.getElementById('payment-pending');
    if (!root) return;

    const pollUrl = root.dataset.pollUrl;
    const failureUrl = root.dataset.failureUrl;
    const timeoutEl = document.getElementById('payment-pending-timeout');
    const maxAttempts = 40;
    let attempts = 0;

    function poll() {
        attempts += 1;

        fetch(pollUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                if (attempts >= maxAttempts) {
                    if (timeoutEl) timeoutEl.hidden = false;
                    return;
                }

                setTimeout(poll, 3000);
            })
            .catch(function () {
                if (attempts >= maxAttempts) {
                    window.location.href = failureUrl;
                    return;
                }

                setTimeout(poll, 3000);
            });
    }

    poll();
})();
</script>
@endsection
