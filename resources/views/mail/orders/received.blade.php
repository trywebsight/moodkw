<x-mail::message>
# {{ __('mail.new_order_title') }}

{{ __('mail.order_details') }}

**{{ __('mail.order_number') }}:** {{ $order->order_number }}
**{{ __('mail.customer') }}:** {{ $order->customer_name }}
**{{ __('mail.phone') }}:** {{ $order->customer_phone }}
**{{ __('mail.total') }}:** {{ number_format((float) $order->total, 3) }} KWD

<x-mail::button :url="\App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order])">
{{ __('mail.view_order') }}
</x-mail::button>

{{ __('mail.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
