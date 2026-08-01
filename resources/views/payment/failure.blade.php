@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    @php
        $settings = \App\Models\Setting::current();
        $storeName = $settings->store_name ?? config('app.name');
        $storeLogo = $settings->store_logo ? asset('storage/'.$settings->store_logo) : null;
        $currency = $settings->currency ?? 'KWD';
        $coverImage = null;
        if ($order?->product) {
            $gallery = $order->product->getGalleryImages();
            $coverImage = isset($gallery[0]) ? asset('storage/'.$gallery[0]) : null;
        }
    @endphp
    <x-menu-header :name="$storeName" :logo="$storeLogo" />

    <main class="menu-wrapper">
        <div class="menu-content menu-content--checkout">
            <div class="payment-status-screen">
                <header class="payment-status-header payment-status-header--error">
                    <div class="payment-status-icon" aria-hidden="true">
                        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="payment-status-heading">
                        <h1 class="bg-title bg-title--compact payment-status-title">{{ __('payment.failed_title') }}</h1>
                        <p class="payment-status-lead">{{ __('payment.failed_hint') }}</p>
                    </div>
                </header>

                @if ($errors->has('payment'))
                    <div class="menu-alert menu-alert--error">{{ $errors->first('payment') }}</div>
                @endif

                @if ($order)
                    <p class="payment-order-ref">
                        {{ __('payment.order_number') }}
                        <span class="menu-value-ltr">{{ $order->order_number }}</span>
                    </p>

                    @if ($order->product)
                        <div class="payment-status-product">
                            @if ($coverImage)
                                <div class="payment-status-product__image">
                                    <img src="{{ $coverImage }}" alt="{{ $order->product->getLocalizedName() }}">
                                </div>
                            @endif
                            <div class="payment-status-product__body">
                                <p class="payment-status-product__name">{{ $order->product->getLocalizedName() }}</p>
                                <p class="payment-status-product__meta">
                                    <span class="menu-value-ltr">{{ $order->quantity }} × {{ number_format($order->unit_price, 3) }} {{ $currency }}</span>
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="menu-review-block menu-review-block--address">
                        <p class="menu-review-block__label">{{ __('checkout.address') }}</p>
                        <div class="menu-review-block__body">
                            @php($buildingLabel = $order->address_type === \App\Enums\AddressType::Home ? __('checkout.house_number') : __('checkout.building'))
                            <p class="menu-review-address-line">{{ $order->address_type->label() }}</p>
                            <p class="menu-review-address-line">{{ __('checkout.block') }} <span class="menu-review-address-num" dir="ltr">{{ $order->block }}</span></p>
                            <p class="menu-review-address-line">{{ __('checkout.street') }} <span class="menu-review-address-num" dir="ltr">{{ $order->street }}</span></p>
                            <p class="menu-review-address-line">{{ $buildingLabel }} <span class="menu-review-address-num" dir="ltr">{{ $order->building }}</span></p>
                            @if ($order->address_type === \App\Enums\AddressType::Apartment)
                                <p class="menu-review-address-line">{{ __('checkout.floor') }} <span class="menu-review-address-num" dir="ltr">{{ $order->floor }}</span></p>
                                <p class="menu-review-address-line">{{ __('checkout.apartment') }} <span class="menu-review-address-num" dir="ltr">{{ $order->apartment }}</span></p>
                            @elseif ($order->address_type === \App\Enums\AddressType::Office)
                                <p class="menu-review-address-line">{{ __('checkout.floor') }} <span class="menu-review-address-num" dir="ltr">{{ $order->floor }}</span></p>
                                @if ($order->apartment)
                                    <p class="menu-review-address-line">{{ __('checkout.office_number') }} <span class="menu-review-address-num" dir="ltr">{{ $order->apartment }}</span></p>
                                @endif
                            @endif
                            @if ($order->delivery_notes)
                                <p class="menu-review-address-notes">{{ $order->delivery_notes }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="payment-status-totals">
                        <div class="payment-status-totals__row">
                            <span>{{ __('checkout.subtotal') }}</span>
                            <span class="payment-status-totals__value menu-value-ltr">{{ number_format($order->subtotal, 3) }} {{ $currency }}</span>
                        </div>
                        <div class="payment-status-totals__row">
                            <span>{{ __('checkout.delivery_fee') }}</span>
                            <span class="payment-status-totals__value menu-value-ltr">{{ number_format($order->delivery_fee, 3) }} {{ $currency }}</span>
                        </div>
                        <div class="payment-status-totals__row payment-status-totals__row--total">
                            <span>{{ __('checkout.total') }}</span>
                            <span class="payment-status-totals__value menu-value-ltr">{{ number_format($order->total, 3) }} {{ $currency }}</span>
                        </div>
                    </div>

                    @if ($order->canRetryPayment())
                        <p class="payment-retry-hint">{{ __('payment.retry_hint') }}</p>
                        <form action="{{ route('payment.retry', $order) }}" method="POST" class="payment-status-actions">
                            @csrf
                            <button type="submit" class="btn-menu-primary btn-menu-primary--block">{{ __('payment.retry_payment') }}</button>
                        </form>
                    @endif
                @endif

                <div class="payment-status-links">
                    <a href="{{ route('home') }}" class="payment-status-link">{{ __('payment.back_home') }}</a>
                    @if ($order?->canRetryPayment())
                        <a href="{{ route('payment.cancel-pending') }}" class="payment-status-link payment-status-link--muted">{{ __('payment.start_new_order') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
