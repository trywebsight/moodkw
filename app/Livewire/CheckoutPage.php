<?php

namespace App\Livewire;

use App\Enums\AddressType;
use App\Models\Order;
use App\Models\Product;
use App\Support\CheckoutAddressRules;
use App\Support\CheckoutValidation;
use App\Support\KuwaitPhone;
use App\Services\DeliveryFeeService;
use App\Services\OrderService;
use App\Services\SettingsService;
use App\Services\WorkingHoursService;
use Livewire\Component;

class CheckoutPage extends Component
{
    private const DRAFT_SESSION_KEY = 'checkout_draft';

    /** @var list<string> */
    private const DRAFT_FIELDS = [
        'step',
        'quantity',
        'customer_name',
        'customer_phone',
        'governorate_id',
        'area_id',
        'address_type',
        'block',
        'street',
        'building',
        'floor',
        'apartment',
        'delivery_notes',
    ];

    public ?Product $product = null;

    public string $customer_name = '';

    public string $customer_phone = '';

    public int $quantity = 1;

    public ?int $governorate_id = null;

    public ?int $area_id = null;

    public string $address_type = AddressType::Home->value;

    public string $block = '';

    public string $street = '';

    public string $building = '';

    public string $floor = '';

    public string $apartment = '';

    public string $delivery_notes = '';

    public int $step = 1;

    public int $stepDirection = 1;

    public function mount(
        DeliveryFeeService $deliveryFeeService,
        SettingsService $settingsService,
    ): void {
        if ($this->redirectToPendingPaymentIfNeeded()) {
            return;
        }

        $this->product = Product::query()->where('is_active', true)->first();

        $governorates = $deliveryFeeService->getActiveGovernorates();
        if ($governorates->isNotEmpty() && ! old('governorate_id')) {
            $this->governorate_id = $governorates->first()->id;
        }

        if (! session()->has('errors')) {
            $this->restoreCheckoutDraft();
        }

        $this->customer_name = $this->stringOrEmpty(old('customer_name', $this->customer_name));
        $this->customer_phone = KuwaitPhone::sanitize($this->stringOrEmpty(old('customer_phone', $this->customer_phone)));
        $this->quantity = (int) old('quantity', $this->quantity);
        $this->governorate_id = old('governorate_id', $this->governorate_id) ? (int) old('governorate_id', $this->governorate_id) : $this->governorate_id;
        $this->area_id = old('area_id', $this->area_id) ? (int) old('area_id', $this->area_id) : $this->area_id;
        $this->address_type = $this->stringOrEmpty(old('address_type', $this->address_type)) ?: AddressType::Home->value;
        $this->block = $this->stringOrEmpty(old('block', $this->block));
        $this->street = $this->stringOrEmpty(old('street', $this->street));
        $this->building = $this->stringOrEmpty(old('building', $this->building));
        $this->floor = $this->stringOrEmpty(old('floor', $this->floor));
        $this->apartment = $this->stringOrEmpty(old('apartment', $this->apartment));
        $this->delivery_notes = $this->stringOrEmpty(old('delivery_notes', $this->delivery_notes));

        $this->normalizeStringFields();

        if (session()->has('errors')) {
            $errors = session('errors')->getBag('default');
            if ($errors->hasAny(['governorate_id', 'area_id', 'address_type', 'block', 'street', 'building', 'floor', 'apartment'])) {
                $this->step = 2;
            } else {
                $this->step = 3;
            }
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 2) {
            $this->validate(
                CheckoutAddressRules::rules($this->address_type),
                CheckoutValidation::messages(),
                CheckoutValidation::attributes(),
            );
        }

        if ($this->step < 3) {
            $this->stepDirection = 1;
            $this->step++;
            $this->persistCheckoutDraft();
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->stepDirection = -1;
            $this->step--;
            $this->persistCheckoutDraft();
        }
    }

    public function updated($property): void
    {
        if ($property === 'customer_phone') {
            $this->customer_phone = KuwaitPhone::sanitize($this->customer_phone);
        }

        if (in_array($property, self::DRAFT_FIELDS, true)) {
            $this->persistCheckoutDraft();
        }
    }

    public function hydrate(): void
    {
        $this->normalizeStringFields();
    }

    private function redirectToPendingPaymentIfNeeded(): bool
    {
        if (session()->has('errors')) {
            return false;
        }

        $orderNumber = session('pending_payment_order');

        if (! $orderNumber) {
            return false;
        }

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order?->canRetryPayment()) {
            session()->forget('pending_payment_order');

            return false;
        }

        $this->redirect(route('payment.failure', ['order' => $orderNumber]));

        return true;
    }

    private function persistCheckoutDraft(): void
    {
        session([
            self::DRAFT_SESSION_KEY => [
                'step' => $this->step,
                'quantity' => $this->quantity,
                'customer_name' => $this->customer_name,
                'customer_phone' => $this->customer_phone,
                'governorate_id' => $this->governorate_id,
                'area_id' => $this->area_id,
                'address_type' => $this->address_type,
                'block' => $this->block,
                'street' => $this->street,
                'building' => $this->building,
                'floor' => $this->floor,
                'apartment' => $this->apartment,
                'delivery_notes' => $this->delivery_notes,
            ],
        ]);
    }

    private function restoreCheckoutDraft(): void
    {
        $draft = session(self::DRAFT_SESSION_KEY);

        if (! is_array($draft)) {
            return;
        }

        if (isset($draft['step'])) {
            $this->step = max(1, min(3, (int) $draft['step']));
        }

        if (isset($draft['quantity'])) {
            $this->quantity = max(1, (int) $draft['quantity']);
        }

        foreach (['customer_name', 'customer_phone', 'address_type', 'block', 'street', 'building', 'floor', 'apartment', 'delivery_notes'] as $field) {
            if (isset($draft[$field]) && is_string($draft[$field])) {
                $this->{$field} = $draft[$field];
            }
        }

        if (isset($draft['governorate_id']) && $draft['governorate_id']) {
            $this->governorate_id = (int) $draft['governorate_id'];
        }

        if (isset($draft['area_id']) && $draft['area_id']) {
            $this->area_id = (int) $draft['area_id'];
        }

        $this->customer_phone = KuwaitPhone::sanitize($this->customer_phone);
        $this->normalizeStringFields();
    }

    private function stringOrEmpty(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function normalizeStringFields(): void
    {
        $this->customer_name = $this->stringOrEmpty($this->customer_name);
        $this->customer_phone = $this->stringOrEmpty($this->customer_phone);
        $this->address_type = $this->stringOrEmpty($this->address_type) ?: AddressType::Home->value;
        $this->block = $this->stringOrEmpty($this->block);
        $this->street = $this->stringOrEmpty($this->street);
        $this->building = $this->stringOrEmpty($this->building);
        $this->floor = $this->stringOrEmpty($this->floor);
        $this->apartment = $this->stringOrEmpty($this->apartment);
        $this->delivery_notes = $this->stringOrEmpty($this->delivery_notes);
    }

    public function updatedGovernorateId(): void
    {
        $this->area_id = null;
    }

    public function updatedAddressType(string $value): void
    {
        if ($value === AddressType::Home->value) {
            $this->floor = '';
            $this->apartment = '';
        }
    }

    public function getGovernoratesProperty(DeliveryFeeService $deliveryFeeService): \Illuminate\Database\Eloquent\Collection
    {
        return $deliveryFeeService->getActiveGovernorates();
    }

    public function getAreasProperty(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->governorate_id) {
            return collect();
        }

        return \App\Models\Area::query()
            ->where('governorate_id', $this->governorate_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getTotalsProperty(OrderService $orderService): array
    {
        if (! $this->product || ! $this->governorate_id) {
            return ['subtotal' => 0, 'delivery_fee' => 0, 'total' => 0];
        }

        return $orderService->calculateTotals(
            $this->product,
            max(1, $this->quantity),
            $this->governorate_id,
        );
    }

    public function getStoreNameProperty(SettingsService $settingsService): string
    {
        return $settingsService->getStoreName();
    }

    public function getStoreLogoProperty(SettingsService $settingsService): ?string
    {
        return $settingsService->getStoreLogoUrl();
    }

    public function getCurrencyProperty(SettingsService $settingsService): string
    {
        return $settingsService->getCurrency();
    }

    public function getTapPublicKeyProperty(SettingsService $settingsService): ?string
    {
        return $settingsService->getTapPublicKey();
    }

    public function getTapMerchantIdProperty(SettingsService $settingsService): ?string
    {
        return $settingsService->getTapMerchantId();
    }

    public function getCardPaymentEnabledProperty(SettingsService $settingsService): bool
    {
        return $settingsService->cardPaymentsEnabled();
    }

    public function getApplePayEnabledProperty(SettingsService $settingsService): bool
    {
        return $settingsService->applePayEnabled();
    }

    public function getKnetEnabledProperty(SettingsService $settingsService): bool
    {
        return $settingsService->knetEnabled();
    }

    public function getCodEnabledProperty(SettingsService $settingsService): bool
    {
        return $settingsService->codEnabled();
    }

    public function getDefaultPaymentMethodProperty(SettingsService $settingsService): ?string
    {
        return $settingsService->defaultPaymentMethod()?->value;
    }

    public function getHasPaymentMethodsProperty(SettingsService $settingsService): bool
    {
        return $settingsService->defaultPaymentMethod() !== null;
    }

    public function getIsOpenProperty(WorkingHoursService $workingHoursService): bool
    {
        return $workingHoursService->isOpen();
    }

    public function getStoreStatusMessageProperty(WorkingHoursService $workingHoursService): string
    {
        return $workingHoursService->getStatusMessage();
    }

    public function getNextOpenTimeProperty(WorkingHoursService $workingHoursService): ?string
    {
        return $workingHoursService->getNextOpenTime();
    }

    public function render()
    {
        return view('livewire.checkout-page')
            ->layout('layouts.app', [
                'title' => app(SettingsService::class)->getSeoTitle(),
            ]);
    }
}
