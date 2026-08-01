<?php

namespace App\Models;

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'delivery_fee',
        'total',
        'governorate_id',
        'area_id',
        'address_type',
        'block',
        'street',
        'avenue',
        'building',
        'floor',
        'apartment',
        'delivery_notes',
        'payment_method',
        'payment_status',
        'order_status',
        'tap_charge_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:3',
            'subtotal' => 'decimal:3',
            'delivery_fee' => 'decimal:3',
            'total' => 'decimal:3',
            'address_type' => AddressType::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'order_status' => OrderStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function canRetryPayment(): bool
    {
        if ($this->payment_method === PaymentMethod::Cod) {
            return false;
        }

        return in_array($this->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true);
    }

    public function signedPaymentUrl(): ?string
    {
        if (! $this->canRetryPayment()) {
            return null;
        }

        return URL::temporarySignedRoute(
            'payment.pay',
            now()->addDays(7),
            ['order' => $this->id],
        );
    }

    public function whatsappPaymentUrl(): ?string
    {
        $paymentUrl = $this->signedPaymentUrl();

        if (! $paymentUrl) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $this->customer_phone);

        if (str_starts_with($phone, '965')) {
            $phone = substr($phone, 3);
        }

        if (strlen($phone) !== 8) {
            return null;
        }

        $message = __('payment.admin_whatsapp_message', [
            'name' => $this->customer_name,
            'order' => $this->order_number,
            'url' => $paymentUrl,
        ], 'ar');

        return 'https://wa.me/965'.$phone.'?text='.urlencode($message);
    }

    public function formatDeliveryAddress(): string
    {
        $buildingLabel = $this->address_type === AddressType::Home
            ? __('checkout.house_number')
            : __('checkout.building');

        $lines = [
            $this->address_type->label(),
            __('checkout.block').' '.$this->block,
            __('checkout.street').' '.$this->street,
            $buildingLabel.' '.$this->building,
        ];

        if ($this->address_type === AddressType::Apartment) {
            $lines[] = __('checkout.floor').' '.$this->floor;
            $lines[] = __('checkout.apartment').' '.$this->apartment;
        } elseif ($this->address_type === AddressType::Office) {
            $lines[] = __('checkout.floor').' '.$this->floor;
            if ($this->apartment) {
                $lines[] = __('checkout.office_number').' '.$this->apartment;
            }
        }

        return implode(', ', $lines);
    }
}
