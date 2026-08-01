<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1d1d1f; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #0071e3; padding-bottom: 15px; }
        .logo { max-height: 50px; }
        .title { font-size: 24px; font-weight: bold; margin: 10px 0; }
        .meta { color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f5f5f7; font-weight: 600; }
        .totals td { border: none; }
        .total-row { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 40px; font-size: 10px; color: #86868b; text-align: center; border-top: 1px solid #eee; padding-top: 15px; }
        .section-title { font-size: 14px; font-weight: 600; margin: 20px 0 8px; color: #0071e3; }
    </style>
</head>
<body>
    <div class="header">
        @if ($settings->store_logo)
            <img src="{{ public_path('storage/'.$settings->store_logo) }}" class="logo" alt="{{ $storeName }}">
        @endif
        <div class="title">{{ $storeName }}</div>
        <div class="meta">INVOICE — {{ $order->order_number }}</div>
        <div class="meta">Date: {{ $order->created_at->format('Y-m-d H:i') }}</div>
        @if ($settings->store_phone)
            <div class="meta">Phone: {{ $settings->store_phone }}</div>
        @endif
    </div>

    <div class="section-title">Customer</div>
    <table>
        <tr><th>Name</th><td>{{ $order->customer_name }}</td></tr>
        <tr><th>Phone</th><td>{{ $order->customer_phone }}</td></tr>
    </table>

    <div class="section-title">Delivery Address</div>
    <table>
        <tr><th>Type</th><td>{{ $order->address_type->label() }}</td></tr>
        <tr><th>Governorate</th><td>{{ $order->governorate->name }}</td></tr>
        <tr><th>Area</th><td>{{ $order->area->name }}</td></tr>
        <tr><th>Block</th><td>{{ $order->block }}</td></tr>
        <tr><th>Street</th><td>{{ $order->street }}</td></tr>
        <tr><th>{{ $order->address_type === \App\Enums\AddressType::Home ? 'House No.' : 'Building' }}</th><td>{{ $order->building }}</td></tr>
        @if (in_array($order->address_type, [\App\Enums\AddressType::Office, \App\Enums\AddressType::Apartment], true) && $order->floor)
            <tr><th>Floor</th><td>{{ $order->floor }}</td></tr>
        @endif
        @if ($order->apartment)
            <tr><th>{{ $order->address_type === \App\Enums\AddressType::Office ? 'Office / Unit' : 'Apartment' }}</th><td>{{ $order->apartment }}</td></tr>
        @endif
        @if ($order->delivery_notes)<tr><th>Notes</th><td>{{ $order->delivery_notes }}</td></tr>@endif
    </table>

    <div class="section-title">Order Items</div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $order->product->name }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ number_format((float) $order->unit_price, 3) }} {{ $currency }}</td>
                <td>{{ number_format((float) $order->subtotal, 3) }} {{ $currency }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td style="text-align:right">{{ number_format((float) $order->subtotal, 3) }} {{ $currency }}</td></tr>
        <tr><td>Delivery Fee</td><td style="text-align:right">{{ number_format((float) $order->delivery_fee, 3) }} {{ $currency }}</td></tr>
        <tr class="total-row"><td>Total</td><td style="text-align:right">{{ number_format((float) $order->total, 3) }} {{ $currency }}</td></tr>
    </table>

    <table>
        <tr><th>Payment Status</th><td>{{ $order->payment_status->label() }}</td></tr>
        <tr><th>Order Status</th><td>{{ $order->order_status->label() }}</td></tr>
        @if ($order->paid_at)
            <tr><th>Paid At</th><td>{{ $order->paid_at->format('Y-m-d H:i') }}</td></tr>
        @endif
    </table>

    <div class="footer">
        Thank you for your order.<br>
        Developed by <strong>websight.kw</strong>
    </div>
</body>
</html>
