@php
    $gallery = $product->getGalleryImages();
    $galleryUrls = collect($gallery)->map(fn ($path) => asset('storage/'.$path))->values()->all();
    $coverImage = $galleryUrls[0] ?? null;
@endphp

<div class="page-wrapper">
    @if ($product)
        <header class="menu-header" wire:key="checkout-header-{{ $step }}">
            <div class="menu-header-inner">
                <div class="menu-header-start">
                    @if ($step > 1 && $this->isOpen)
                        <button type="button" wire:click="previousStep" class="menu-header-back" aria-label="{{ __('checkout.back') }}">
                            <svg class="menu-header-back-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    @endif
                    <a href="{{ route('home') }}" class="menu-header-logo">
                        @if ($this->storeLogo)
                            <img src="{{ $this->storeLogo }}" alt="{{ $this->storeName }}" width="40" height="40">
                        @endif
                        <span class="menu-header-name">{{ $this->storeName }}</span>
                    </a>
                </div>
                <div class="menu-lang">
                    <a href="{{ route('locale.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en'])">EN</a>
                    <a href="{{ route('locale.switch', 'ar') }}" @class(['is-active' => app()->getLocale() === 'ar'])">عربي</a>
                </div>
            </div>
        </header>
    @else
        <x-menu-header :name="$this->storeName" :logo="$this->storeLogo" />
    @endif

    @if (!$product)
        <main class="menu-wrapper">
            <div class="menu-content">
                <p class="text-center" style="color: var(--menu-span-color); font-size: 18px;">{{ __('checkout.no_product') }}</p>
            </div>
        </main>
    @else
        <main class="menu-wrapper">
            <div @class([
                'menu-content menu-content--checkout',
                'menu-content--dock' => $this->isOpen && ($step === 1 || $step === 2),
            ])>

                @if ($this->isOpen)
                    <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form" autocomplete="on">
                        @csrf
                        <div class="hidden" aria-hidden="true">
                            <input type="hidden" name="quantity" value="{{ $quantity }}">
                            @if ($step !== 3)
                                <input type="hidden" name="customer_name" value="{{ $customer_name }}">
                                <input type="hidden" name="customer_phone" value="{{ $customer_phone }}">
                            @endif
                            @if ($step !== 2)
                                <input type="hidden" name="governorate_id" value="{{ $governorate_id }}">
                                <input type="hidden" name="area_id" value="{{ $area_id }}">
                                <input type="hidden" name="address_type" value="{{ $address_type }}">
                                <input type="hidden" name="block" value="{{ $block }}">
                                <input type="hidden" name="street" value="{{ $street }}">
                                <input type="hidden" name="building" value="{{ $building }}">
                                @if (in_array($address_type, ['office', 'apartment'], true))
                                    <input type="hidden" name="floor" value="{{ $floor }}">
                                @endif
                                @if ($address_type !== 'home')
                                    <input type="hidden" name="apartment" value="{{ $apartment }}">
                                @endif
                                <input type="hidden" name="delivery_notes" value="{{ $delivery_notes }}">
                            @endif
                        </div>

                        @error('checkout')
                            <div class="menu-alert menu-alert--error">{{ $message }}</div>
                        @enderror
                        @error('product')
                            <div class="menu-alert menu-alert--error">{{ $message }}</div>
                        @enderror
                        @error('quantity')
                            <div class="menu-alert menu-alert--error">{{ $message }}</div>
                        @enderror

                        <div
                            class="checkout-flow-pane"
                            wire:key="checkout-step-{{ $step }}"
                            @class([
                                'checkout-flow-pane--forward' => $stepDirection > 0,
                                'checkout-flow-pane--back' => $stepDirection < 0,
                            ])
                        >

                        {{-- Step 1: Product hero + quantity (menu-vue product screen) --}}
                        @if ($step === 1)
                            <div class="checkout-screen">
                                <article class="products-item products-item--hero"
                                    @if (count($gallery) > 1)
                                        x-data="{
                                            index: 0,
                                            images: @js($galleryUrls),
                                            timer: null,
                                            start() {
                                                this.timer = setInterval(() => {
                                                    this.index = (this.index + 1) % this.images.length;
                                                }, 4000);
                                            },
                                            select(i) {
                                                this.index = i;
                                                clearInterval(this.timer);
                                                this.start();
                                            }
                                        }"
                                        x-init="start()"
                                    @endif
                                >
                                    <div class="products-image products-image--hero">
                                        @if (count($gallery) > 0)
                                            @if (count($gallery) > 1)
                                                <img :src="images[index]" :key="index" alt="{{ $product->getLocalizedName() }}">
                                            @else
                                                <img src="{{ $coverImage }}" alt="{{ $product->getLocalizedName() }}">
                                            @endif
                                            <div class="products-price">{{ number_format($product->price, 3) }} {{ $this->currency }}</div>
                                        @else
                                            <div class="products-image-placeholder products-image-placeholder--hero"></div>
                                        @endif
                                    </div>

                                    @if (count($gallery) > 1)
                                        <div class="products-gallery-thumbs">
                                            @foreach ($gallery as $index => $path)
                                                <button type="button" @click="select({{ $index }})"
                                                    :class="{ 'is-active': index === {{ $index }} }"
                                                    class="products-gallery-thumb"
                                                    aria-label="{{ __('checkout.photo') }} {{ $index + 1 }}">
                                                    <img src="{{ asset('storage/'.$path) }}" alt="">
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="products-content">
                                        <h1 class="products-name">{{ $product->getLocalizedName() }}</h1>
                                        @if ($product->getLocalizedDescription())
                                            <p class="products-description">{{ $product->getLocalizedDescription() }}</p>
                                        @endif
                                    </div>
                                </article>

                                @if ($product->stock <= 5)
                                    <p class="menu-stock-note menu-stock-note--inline">{{ __('checkout.only_left', ['count' => $product->stock]) }}</p>
                                @endif
                            </div>
                        @endif

                        @if ($step === 2)
                            <div class="checkout-screen">
                                <section class="menu-form-section checkout-screen-section checkout-screen-section--compact">
                                    <h2 class="checkout-screen-title">{{ __('checkout.delivery_address') }}</h2>
                                    <div class="menu-field menu-field--tight">
                                        <div class="menu-address-type" role="group" aria-label="{{ __('checkout.address_type') }}">
                                            @foreach (\App\Enums\AddressType::cases() as $type)
                                                <button type="button"
                                                    wire:click="$set('address_type', '{{ $type->value }}')"
                                                    @class([
                                                        'menu-address-type-btn',
                                                        'is-active' => $address_type === $type->value,
                                                    ])>
                                                    <x-address-type-icon :type="$type" />
                                                    <span class="menu-address-type-label">{{ $type->label() }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                        <input type="hidden" name="address_type" value="{{ $address_type }}">
                                        @error('address_type') <p class="menu-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="menu-grid-2 menu-grid-2--tight">
                                        <div class="menu-field menu-field--tight">
                                            <label class="menu-label" for="governorate_id">{{ __('checkout.governorate') }}</label>
                                            <select id="governorate_id" name="governorate_id" wire:model.live="governorate_id"
                                                required autocomplete="address-level1"
                                                @class(['menu-select', 'menu-input--error' => $errors->has('governorate_id')])>
                                                @foreach ($this->governorates as $gov)
                                                    <option value="{{ $gov->id }}">
                                                        {{ app()->getLocale() === 'ar' && $gov->name_ar ? $gov->name_ar : $gov->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('governorate_id') <p class="menu-field-error">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="menu-field menu-field--tight">
                                            <label class="menu-label" for="area_id">{{ __('checkout.area') }}</label>
                                            <select id="area_id" name="area_id" wire:model.blur="area_id" required
                                                autocomplete="address-level2"
                                                @class(['menu-select', 'menu-input--error' => $errors->has('area_id')])>
                                                <option value="">{{ __('checkout.select_area') }}</option>
                                                @foreach ($this->areas as $area)
                                                    <option value="{{ $area->id }}">
                                                        {{ app()->getLocale() === 'ar' && $area->name_ar ? $area->name_ar : $area->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('area_id') <p class="menu-field-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="menu-grid-address menu-grid-address--tight">
                                        <div class="menu-field menu-field--tight">
                                            <label class="menu-label" for="block">{{ __('checkout.block') }}</label>
                                            <input type="text" id="block" name="block" wire:model.blur="block" required
                                                autocomplete="address-line3" inputmode="numeric"
                                                @class(['menu-input', 'menu-input--compact', 'menu-input--error' => $errors->has('block')])>
                                            @error('block') <p class="menu-field-error">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="menu-field menu-field--tight">
                                            <label class="menu-label" for="building">
                                                {{ $address_type === 'home' ? __('checkout.house_number') : __('checkout.building') }}
                                            </label>
                                            <input type="text" id="building" name="building" wire:model.blur="building" required
                                                autocomplete="address-line2" inputmode="numeric"
                                                @class(['menu-input', 'menu-input--compact', 'menu-input--error' => $errors->has('building')])>
                                            @error('building') <p class="menu-field-error">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="menu-field menu-field--tight">
                                            <label class="menu-label" for="street">{{ __('checkout.street') }}</label>
                                            <input type="text" id="street" name="street" wire:model.blur="street" required
                                                autocomplete="street-address" inputmode="numeric"
                                                @class(['menu-input', 'menu-input--compact', 'menu-input--error' => $errors->has('street')])>
                                            @error('street') <p class="menu-field-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div
                                        class="checkout-address-fields checkout-address-fields--in"
                                        wire:key="address-fields-{{ $address_type }}"
                                    >
                                    @if ($address_type === 'apartment')
                                        <div class="menu-grid-2 menu-grid-2--tight">
                                            <div class="menu-field menu-field--tight">
                                                <label class="menu-label" for="floor">{{ __('checkout.floor') }}</label>
                                                <input type="text" id="floor" name="floor" wire:model.blur="floor" required
                                                    autocomplete="address-line2" inputmode="numeric"
                                                    @class(['menu-input', 'menu-input--compact', 'menu-input--error' => $errors->has('floor')])>
                                                @error('floor') <p class="menu-field-error">{{ $message }}</p> @enderror
                                            </div>
                                            <div class="menu-field menu-field--tight">
                                                <label class="menu-label" for="apartment">{{ __('checkout.apartment') }}</label>
                                                <input type="text" id="apartment" name="apartment" wire:model.blur="apartment" required
                                                    autocomplete="address-line2" inputmode="numeric"
                                                    @class(['menu-input', 'menu-input--compact', 'menu-input--error' => $errors->has('apartment')])>
                                                @error('apartment') <p class="menu-field-error">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    @elseif ($address_type === 'office')
                                        <div class="menu-grid-2 menu-grid-2--tight">
                                            <div class="menu-field menu-field--tight">
                                                <label class="menu-label" for="floor">{{ __('checkout.floor') }}</label>
                                                <input type="text" id="floor" name="floor" wire:model.blur="floor" required
                                                    autocomplete="address-line2" inputmode="numeric"
                                                    @class(['menu-input', 'menu-input--compact', 'menu-input--error' => $errors->has('floor')])>
                                                @error('floor') <p class="menu-field-error">{{ $message }}</p> @enderror
                                            </div>
                                            <div class="menu-field menu-field--tight">
                                                <label class="menu-label" for="apartment">{{ __('checkout.office_number') }}</label>
                                                <input type="text" id="apartment" name="apartment" wire:model.blur="apartment"
                                                    autocomplete="address-line2" class="menu-input menu-input--compact"
                                                    placeholder="{{ __('checkout.optional') }}">
                                                @error('apartment') <p class="menu-field-error">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    @endif
                                    </div>

                                    <div class="menu-field menu-field--tight menu-field--last">
                                        <input type="text" id="delivery_notes" name="delivery_notes" wire:model.blur="delivery_notes"
                                            autocomplete="off" class="menu-input menu-input--compact"
                                            placeholder="{{ __('checkout.delivery_notes_placeholder') }}">
                                    </div>
                                </section>
                            </div>
                        @endif

                        @if ($step === 3)
                            <div class="checkout-screen">
                                <section class="menu-form-section checkout-screen-section checkout-screen-section--compact">
                                    <h2 class="checkout-screen-title">{{ __('checkout.your_details') }}</h2>
                                    <div class="menu-field menu-field--tight">
                                        <label class="menu-label" for="customer_name">{{ __('checkout.full_name') }}</label>
                                        <input type="text" id="customer_name" name="customer_name"
                                            wire:model.blur="customer_name" required autocomplete="name"
                                            @animationstart="if ($event.animationName === 'menu-autofill-start') $wire.set('customer_name', $el.value)"
                                            @class(['menu-input', 'menu-input--error' => $errors->has('customer_name')])
                                            placeholder="{{ __('checkout.full_name_placeholder') }}">
                                        @error('customer_name') <p class="menu-field-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="menu-field menu-field--tight">
                                        <label class="menu-label" for="customer_phone">{{ __('checkout.mobile_number') }}</label>
                                        <input type="tel" id="customer_phone" name="customer_phone"
                                            wire:model.live="customer_phone" required dir="ltr"
                                            autocomplete="tel-national" inputmode="numeric"
                                            maxlength="8" pattern="[24569][0-9]{7}"
                                            @animationstart="if ($event.animationName === 'menu-autofill-start') $wire.set('customer_phone', $el.value)"
                                            @class(['menu-input', 'menu-input--tel', 'menu-input--error' => $errors->has('customer_phone')])
                                            placeholder="{{ __('checkout.mobile_placeholder') }}">
                                        @error('customer_phone') <p class="menu-field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="products-item products-item--checkout">
                                        <div class="products-image products-image--checkout">
                                            @if ($coverImage)
                                                <img src="{{ $coverImage }}" alt="{{ $product->getLocalizedName() }}">
                                            @endif
                                        </div>
                                        <div class="products-content products-content--checkout">
                                            <p class="products-name">{{ $product->getLocalizedName() }}</p>
                                            <p class="products-checkout-meta" dir="ltr">
                                                {{ $quantity }} × {{ number_format($product->price, 3) }} {{ $this->currency }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="menu-review-block menu-review-block--address">
                                        <p class="menu-review-block__label">{{ __('checkout.address') }}</p>
                                        <div class="menu-review-block__body">
                                            @php($addrType = \App\Enums\AddressType::from($address_type))
                                            @php($buildingLabel = $address_type === 'home' ? __('checkout.house_number') : __('checkout.building'))
                                            <p class="menu-review-address-line">{{ $addrType->label() }}</p>
                                            <p class="menu-review-address-line">{{ __('checkout.block') }} <span class="menu-review-address-num" dir="ltr">{{ $block }}</span></p>
                                            <p class="menu-review-address-line">{{ __('checkout.street') }} <span class="menu-review-address-num" dir="ltr">{{ $street }}</span></p>
                                            <p class="menu-review-address-line">{{ $buildingLabel }} <span class="menu-review-address-num" dir="ltr">{{ $building }}</span></p>
                                            @if ($address_type === 'apartment')
                                                <p class="menu-review-address-line">{{ __('checkout.floor') }} <span class="menu-review-address-num" dir="ltr">{{ $floor }}</span></p>
                                                <p class="menu-review-address-line">{{ __('checkout.apartment') }} <span class="menu-review-address-num" dir="ltr">{{ $apartment }}</span></p>
                                            @elseif ($address_type === 'office')
                                                <p class="menu-review-address-line">{{ __('checkout.floor') }} <span class="menu-review-address-num" dir="ltr">{{ $floor }}</span></p>
                                                @if ($apartment)
                                                    <p class="menu-review-address-line">{{ __('checkout.office_number') }} <span class="menu-review-address-num" dir="ltr">{{ $apartment }}</span></p>
                                                @endif
                                            @endif
                                            @if ($delivery_notes)
                                                <p class="menu-review-address-notes">{{ $delivery_notes }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="summary checkout-summary">
                                        <div class="summary-item">
                                            <span>{{ __('checkout.subtotal') }}</span>
                                            <b dir="ltr">{{ number_format($this->totals['subtotal'], 3) }} {{ $this->currency }}</b>
                                        </div>
                                        <div class="summary-item">
                                            <span>{{ __('checkout.delivery_fee') }}</span>
                                            <b dir="ltr">{{ number_format($this->totals['delivery_fee'], 3) }} {{ $this->currency }}</b>
                                        </div>
                                        <div class="summary-item summary-item--total">
                                            <span>{{ __('checkout.total') }}</span>
                                            <b dir="ltr">{{ number_format($this->totals['total'], 3) }} {{ $this->currency }}</b>
                                        </div>
                                    </div>

                                    <div
                                        class="menu-payment-section"
                                        wire:ignore.self
                                        x-data="checkoutPayment(@js([
                                            'defaultMethod' => $this->defaultPaymentMethod,
                                            'publicKey' => $this->tapPublicKey,
                                            'merchantId' => $this->tapMerchantId,
                                            'knetEnabled' => $this->knetEnabled,
                                            'cardEnabled' => $this->cardPaymentEnabled,
                                            'applePayEnabled' => $this->applePayEnabled,
                                            'codEnabled' => $this->codEnabled,
                                            'amount' => max(1, (float) $this->totals['total']),
                                            'currency' => $this->currency,
                                            'locale' => app()->getLocale(),
                                            'liveMode' => app(\App\Services\SettingsService::class)->isLiveMode(),
                                            'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
                                            'customer' => [
                                                'firstName' => $customer_name ?: 'Guest',
                                                'lastName' => '',
                                                'phoneCountryCode' => '965',
                                                'phoneNumber' => $customer_phone,
                                            ],
                                            'errorGeneric' => __('checkout.payment_error_generic'),
                                            'errorCardUnavailable' => __('checkout.payment_card_unavailable'),
                                            'confirmOrderLabel' => __('checkout.confirm_order'),
                                        ]))"
                                    >
                                        <h2 class="checkout-screen-title">{{ __('checkout.payment_method') }}</h2>

                                        @error('payment_method')
                                            <div class="menu-alert menu-alert--error">{{ $message }}</div>
                                        @enderror
                                        @error('payment_token')
                                            <div class="menu-alert menu-alert--error">{{ $message }}</div>
                                        @enderror

                                        <input type="hidden" name="payment_method" x-model="method">
                                        <input type="hidden" name="payment_token" x-model="token">

                                        <div class="menu-payment-methods">
                                            @if ($this->knetEnabled)
                                                <button
                                                    type="button"
                                                    class="menu-payment-method"
                                                    :class="{ 'menu-payment-method--active': method === 'knet' }"
                                                    @click="selectMethod('knet')"
                                                >
                                                    <span class="menu-payment-method__radio" aria-hidden="true"></span>
                                                    <span class="menu-payment-method__body">
                                                        <span class="menu-payment-method__label">{{ __('checkout.payment_knet') }}</span>
                                                        <span class="menu-payment-method__hint">{{ __('checkout.payment_knet_hint') }}</span>
                                                    </span>
                                                    <span class="menu-payment-method__badge">KNET</span>
                                                </button>
                                            @endif

                                            @if ($this->cardPaymentEnabled)
                                                <button
                                                    type="button"
                                                    class="menu-payment-method"
                                                    :class="{ 'menu-payment-method--active': method === 'card' }"
                                                    @click="selectMethod('card')"
                                                >
                                                    <span class="menu-payment-method__radio" aria-hidden="true"></span>
                                                    <span class="menu-payment-method__body">
                                                        <span class="menu-payment-method__label">{{ __('checkout.payment_card') }}</span>
                                                        <span class="menu-payment-method__hint">{{ __('checkout.payment_card_hint') }}</span>
                                                    </span>
                                                    <span class="menu-payment-method__badge menu-payment-method__badge--card">CARD</span>
                                                </button>
                                            @endif

                                            @if ($this->applePayEnabled)
                                                <button
                                                    type="button"
                                                    class="menu-payment-method"
                                                    :class="{ 'menu-payment-method--active': method === 'apple_pay' }"
                                                    @click="selectMethod('apple_pay')"
                                                >
                                                    <span class="menu-payment-method__radio" aria-hidden="true"></span>
                                                    <span class="menu-payment-method__body">
                                                        <span class="menu-payment-method__label">{{ __('checkout.payment_apple_pay') }}</span>
                                                        <span class="menu-payment-method__hint">{{ __('checkout.payment_apple_pay_hint') }}</span>
                                                    </span>
                                                    <span class="menu-payment-method__badge menu-payment-method__badge--apple">Apple Pay</span>
                                                </button>
                                            @endif

                                            @if ($this->codEnabled)
                                                <button
                                                    type="button"
                                                    class="menu-payment-method"
                                                    :class="{ 'menu-payment-method--active': method === 'cod' }"
                                                    @click="selectMethod('cod')"
                                                >
                                                    <span class="menu-payment-method__radio" aria-hidden="true"></span>
                                                    <span class="menu-payment-method__body">
                                                        <span class="menu-payment-method__label">{{ __('checkout.payment_cod') }}</span>
                                                        <span class="menu-payment-method__hint">{{ __('checkout.payment_cod_hint') }}</span>
                                                    </span>
                                                    <span class="menu-payment-method__badge menu-payment-method__badge--cod">COD</span>
                                                </button>
                                            @endif
                                        </div>

                                        <div x-show="method === 'card'" x-cloak class="menu-payment-panel" wire:ignore>
                                            <div x-ref="cardMount" class="menu-payment-panel__mount"></div>
                                        </div>

                                        <div x-show="method === 'apple_pay'" x-cloak class="menu-payment-panel menu-payment-panel--apple" wire:ignore>
                                            <div x-ref="applePayMount" class="menu-payment-panel__mount menu-payment-panel__mount--apple"></div>
                                        </div>

                                        <p x-show="error" x-text="error" class="menu-field-error menu-payment-error" x-cloak></p>

                                        <div class="menu-payment-actions" x-show="method !== 'apple_pay'" x-cloak>
                                            <button
                                                type="button"
                                                class="btn-menu-primary btn-menu-primary--block"
                                                :disabled="loading"
                                                @click="pay()"
                                            >
                                                <span x-text="method === 'cod' ? config.confirmOrderLabel : @js(__('checkout.pay'))"></span>
                                                <span class="btn-menu-primary-price menu-value-ltr">{{ number_format($this->totals['total'], 3) }} {{ $this->currency }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        @endif

                        </div>

                    </form>
                @else
                    <div class="store-closed-screen checkout-flow-fade" wire:key="store-closed">
                        <article
                            class="store-closed-hero"
                            @if (count($gallery) > 1)
                                x-data="{
                                    index: 0,
                                    images: @js($galleryUrls),
                                    timer: null,
                                    start() {
                                        this.timer = setInterval(() => {
                                            this.index = (this.index + 1) % this.images.length;
                                        }, 3800);
                                    }
                                }"
                                x-init="start()"
                            @endif
                        >
                            @if (count($gallery) > 0)
                                <div class="store-closed-hero__media">
                                    @foreach ($galleryUrls as $i => $url)
                                        <img
                                            src="{{ $url }}"
                                            alt="{{ $product->getLocalizedName() }}"
                                            @class(['store-closed-hero__img', 'is-active' => count($gallery) === 1])
                                            @if (count($gallery) > 1)
                                                :class="{ 'is-active': index === {{ $i }} }"
                                            @endif
                                        >
                                    @endforeach
                                </div>
                            @else
                                <div class="store-closed-hero__placeholder" aria-hidden="true"></div>
                            @endif
                            <div class="store-closed-hero__shade" aria-hidden="true"></div>
                            <div class="store-closed-hero__badge">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span>{{ __('checkout.store_closed_badge') }}</span>
                            </div>
                        </article>

                        @if (count($galleryUrls) > 0)
                            <div class="store-closed-filmstrip" aria-hidden="true">
                                <div class="store-closed-filmstrip__track">
                                    @foreach (array_merge($galleryUrls, $galleryUrls) as $url)
                                        <img src="{{ $url }}" alt="" class="store-closed-filmstrip__img" loading="lazy">
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="store-closed-card">
                            <h2 class="store-closed-card__name">{{ $product->getLocalizedName() }}</h2>
                            @if ($product->getLocalizedDescription())
                                <p class="store-closed-card__desc">{{ $product->getLocalizedDescription() }}</p>
                            @endif
                            <p class="store-closed-card__message">{{ $this->storeStatusMessage }}</p>
                            @if ($this->nextOpenTime)
                                <p class="store-closed-card__opens">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span>{{ $this->nextOpenTime }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if ($this->isOpen)
                <div
                    @class([
                        'menu-checkout-actions checkout-flow-fade',
                        'menu-checkout-actions--dock' => $step === 2,
                        'menu-checkout-actions--step1' => $step === 1,
                    ])
                    wire:key="checkout-actions-{{ $step }}"
                >
                    @if ($step === 2)
                        <div class="checkout-dock-product">
                            @if ($coverImage)
                                <img src="{{ $coverImage }}" alt="" class="checkout-dock-product__thumb">
                            @endif
                            <p class="checkout-dock-product__name">{{ $product->getLocalizedName() }}</p>
                        </div>
                    @endif
                    @if ($step === 1)
                        <div class="checkout-cta-bar btn-menu-primary btn-menu-primary--checkout">
                            <div class="checkout-cta-counter" role="group" aria-label="{{ __('checkout.quantity') }}">
                                <button type="button" class="checkout-cta-counter-btn"
                                    wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                                    @if($quantity <= 1) disabled @endif aria-label="{{ __('checkout.decrease_quantity') }}">&minus;</button>
                                <span class="checkout-cta-counter-value">{{ $quantity }}</span>
                                <button type="button" class="checkout-cta-counter-btn"
                                    wire:click="$set('quantity', {{ min($product->stock, $quantity + 1) }})"
                                    @if($quantity >= $product->stock) disabled @endif aria-label="{{ __('checkout.increase_quantity') }}">+</button>
                            </div>
                            <button type="button" wire:click="nextStep" class="checkout-cta-submit checkout-cta-submit--solo">
                                <span>{{ __('checkout.add_to_cart') }}</span>
                            </button>
                        </div>
                    @elseif ($step === 2)
                        <button type="button" wire:click="nextStep" class="btn-menu-primary btn-menu-primary--checkout btn-menu-primary--solo">
                            <span>{{ __('checkout.continue') }}</span>
                        </button>
                    @endif
                </div>
            @endif
        </main>
    @endif

    <footer class="menu-footer">
        <p class="menu-footer-line">
            <a href="https://www.instagram.com/mood.sweet.kw/" target="_blank" rel="noopener noreferrer">@mood.sweet.kw</a>
            <span class="menu-footer-sep" aria-hidden="true">·</span>
            <a href="https://wa.me/96550587086" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            <span class="menu-footer-sep" aria-hidden="true">·</span>
            <span>{{ $this->storeName }} &copy; {{ date('Y') }}</span>
            <span class="menu-footer-sep" aria-hidden="true">·</span>
            <a href="https://websight.kw" target="_blank" rel="noopener noreferrer" class="menu-footer-websight">
                <img src="https://tryriders.com/assets/websight-logo.svg" alt="Websight" height="14" width="60">
            </a>
        </p>
    </footer>
</div>

@push('scripts')
    @vite('resources/js/checkout-payment.js')
@endpush
