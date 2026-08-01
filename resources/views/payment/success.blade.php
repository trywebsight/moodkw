@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    @php
        $settings = \App\Models\Setting::current();
        $storeName = $settings->store_name ?? config('app.name');
        $storeLogo = $settings->store_logo ? asset('storage/'.$settings->store_logo) : null;
        $currency = $settings->currency ?? 'KWD';
        $coverImage = null;
        if ($order->product) {
            $gallery = $order->product->getGalleryImages();
            $coverImage = isset($gallery[0]) ? asset('storage/'.$gallery[0]) : null;
        }
    @endphp
    <x-menu-header :name="$storeName" :logo="$storeLogo" />

    <main class="menu-wrapper">
        <div class="menu-content menu-content--checkout">
            <div class="payment-status-screen">
                <header class="payment-status-header payment-status-header--success">
                    <div class="payment-status-icon" aria-hidden="true">
                        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="payment-status-heading">
                        <h1 class="bg-title bg-title--compact payment-status-title">{{ __('payment.success_title') }}</h1>
                        <p class="payment-status-lead">{{ session('cod_order') ? __('payment.success_cod_hint') : __('payment.success_hint') }}</p>
                    </div>
                </header>

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

                <p class="payment-status-followup">{{ __('payment.success_followup') }}</p>

                <div class="payment-status-actions">
                    <a href="{{ route('invoices.download', $order) }}" class="btn-menu-outline btn-menu-primary--block">{{ __('payment.download_invoice') }}</a>
                    <a href="{{ route('home') }}" class="btn-menu-primary btn-menu-primary--block">{{ __('payment.back_home') }}</a>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
