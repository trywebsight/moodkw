<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Setting;

class SettingsService
{
    public function get(): Setting
    {
        return Setting::current();
    }

    public function getCurrency(): string
    {
        return $this->get()->currency ?? config('app.currency', 'KWD');
    }

    public function getTapSecretKey(): ?string
    {
        $setting = $this->get()->tap_secret_key;

        if ($setting) {
            return $setting;
        }

        return config('services.tap.secret_key') ?: env('TAP_SECRET_KEY');
    }

    public function getTapPublicKey(): ?string
    {
        $setting = $this->get()->tap_public_key;

        if ($setting) {
            return $setting;
        }

        return config('services.tap.public_key') ?: env('TAP_PUBLIC_KEY');
    }

    public function isLiveMode(): bool
    {
        $mode = $this->get()->tap_mode?->value ?? env('TAP_MODE', 'test');

        return $mode === 'live';
    }

    public function getTapMerchantId(): ?string
    {
        $merchantId = config('tap.merchant_id') ?: env('TAP_MERCHANT_ID');

        return is_string($merchantId) && $merchantId !== '' ? $merchantId : null;
    }

    public function cardPaymentsAvailable(): bool
    {
        return filled($this->getTapPublicKey()) && filled($this->getTapMerchantId());
    }

    public function isPaymentMethodEnabled(PaymentMethod $method): bool
    {
        $setting = $this->get();

        return match ($method) {
            PaymentMethod::Knet => (bool) ($setting->payment_knet_enabled ?? true)
                && filled($this->getTapSecretKey()),
            PaymentMethod::Card => (bool) ($setting->payment_card_enabled ?? true)
                && $this->cardPaymentsAvailable(),
            PaymentMethod::ApplePay => (bool) ($setting->payment_apple_pay_enabled ?? true)
                && $this->cardPaymentsAvailable(),
            PaymentMethod::Cod => (bool) ($setting->payment_cod_enabled ?? false),
        };
    }

    /**
     * @return list<PaymentMethod>
     */
    public function enabledPaymentMethods(): array
    {
        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $method): bool => $this->isPaymentMethodEnabled($method),
        ));
    }

    public function knetEnabled(): bool
    {
        return $this->isPaymentMethodEnabled(PaymentMethod::Knet);
    }

    public function cardPaymentsEnabled(): bool
    {
        return $this->isPaymentMethodEnabled(PaymentMethod::Card);
    }

    public function applePayEnabled(): bool
    {
        return $this->isPaymentMethodEnabled(PaymentMethod::ApplePay);
    }

    public function codEnabled(): bool
    {
        return $this->isPaymentMethodEnabled(PaymentMethod::Cod);
    }

    public function defaultPaymentMethod(): ?PaymentMethod
    {
        return $this->enabledPaymentMethods()[0] ?? null;
    }

    public function getStoreName(): string
    {
        return $this->get()->store_name ?? config('app.name');
    }

    public function getStoreLogoUrl(): ?string
    {
        $logo = $this->get()->store_logo;

        if ($logo) {
            return asset('storage/'.$logo);
        }

        foreach ([
            'images/mood-logo.svg',
            'images/mood-logo.jpg',
            'storage/store/mood-logo.svg',
            'storage/store/mood-logo.jpg',
        ] as $path) {
            if (is_file(public_path($path))) {
                return asset($path);
            }
        }

        return null;
    }

    public function getSeoTitle(): string
    {
        $settings = $this->get();

        if (app()->getLocale() === 'ar') {
            return $settings->seo_title_ar ?? $settings->seo_title ?? $this->getStoreName();
        }

        return $settings->seo_title ?? $this->getStoreName();
    }

    public function getSeoDescription(): string
    {
        $settings = $this->get();

        if (app()->getLocale() === 'ar') {
            return $settings->seo_description_ar ?? $settings->seo_description ?? __('seo.default_description');
        }

        return $settings->seo_description ?? __('seo.default_description');
    }
}
