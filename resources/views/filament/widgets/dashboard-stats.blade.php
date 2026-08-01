@php
    $formatKwd = fn (float $amount): string => number_format($amount, 3).' KWD';
@endphp

<div class="mood-dashboard">
    <div class="mood-dashboard__hero-grid">
        <a href="{{ $ordersUrl }}" class="mood-dashboard__hero mood-dashboard__hero--primary">
            <div class="mood-dashboard__hero-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-6 w-6" />
            </div>
            <div class="mood-dashboard__hero-body">
                <p class="mood-dashboard__hero-label">Today's revenue</p>
                <p class="mood-dashboard__hero-value">{{ $formatKwd($todayRevenue) }}</p>
                <p class="mood-dashboard__hero-meta">{{ $todayOrders }} {{ str('order')->plural($todayOrders) }} today</p>
            </div>
        </a>

        <a href="{{ $paidOrdersUrl }}" class="mood-dashboard__hero mood-dashboard__hero--secondary">
            <div class="mood-dashboard__hero-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-banknotes" class="h-6 w-6" />
            </div>
            <div class="mood-dashboard__hero-body">
                <p class="mood-dashboard__hero-label">Total revenue</p>
                <p class="mood-dashboard__hero-value">{{ $formatKwd($revenue) }}</p>
                <p class="mood-dashboard__hero-meta">{{ $paidOrders }} paid {{ str('order')->plural($paidOrders) }}</p>
            </div>
        </a>
    </div>

    <div class="mood-dashboard__metrics">
        <a href="{{ $ordersUrl }}" class="mood-dashboard__metric">
            <span class="mood-dashboard__metric-label">Total orders</span>
            <span class="mood-dashboard__metric-value">{{ number_format($totalOrders) }}</span>
            <span class="mood-dashboard__metric-hint">All time</span>
        </a>

        <a href="{{ $ordersUrl }}" class="mood-dashboard__metric">
            <span class="mood-dashboard__metric-label">Today</span>
            <span class="mood-dashboard__metric-value">{{ number_format($todayOrders) }}</span>
            <span class="mood-dashboard__metric-hint">Since midnight</span>
        </a>

        <a href="{{ $paidOrdersUrl }}" class="mood-dashboard__metric mood-dashboard__metric--success">
            <span class="mood-dashboard__metric-label">Paid</span>
            <span class="mood-dashboard__metric-value">{{ number_format($paidOrders) }}</span>
            <span class="mood-dashboard__metric-hint">Successful payments</span>
        </a>

        <a href="{{ $pendingOrdersUrl }}" class="mood-dashboard__metric mood-dashboard__metric--warning">
            <span class="mood-dashboard__metric-label">Pending</span>
            <span class="mood-dashboard__metric-value">{{ number_format($pendingPayments) }}</span>
            <span class="mood-dashboard__metric-hint">Awaiting payment</span>
        </a>
    </div>

    <div class="mood-dashboard__charts">
        <div class="mood-dashboard__chart-card">
            <div class="mood-dashboard__chart-header">
                <h3 class="mood-dashboard__chart-title">Orders — last 7 days</h3>
            </div>
            <div class="mood-dashboard__bars" role="img" aria-label="Orders per day for the last 7 days">
                @foreach ($weeklyOrders as $day)
                    <div class="mood-dashboard__bar-col">
                        <div class="mood-dashboard__bar-shell">
                            <div
                                class="mood-dashboard__bar mood-dashboard__bar--orders"
                                style="height: {{ max(12, ($day['value'] / $maxWeeklyOrders) * 100) }}%"
                            ></div>
                        </div>
                        <span class="mood-dashboard__bar-label">{{ $day['label'] }}</span>
                        <span class="mood-dashboard__bar-value">{{ $day['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mood-dashboard__chart-card">
            <div class="mood-dashboard__chart-header">
                <h3 class="mood-dashboard__chart-title">Revenue — last 7 days</h3>
            </div>
            <div class="mood-dashboard__bars" role="img" aria-label="Revenue per day for the last 7 days">
                @foreach ($weeklyRevenue as $day)
                    <div class="mood-dashboard__bar-col">
                        <div class="mood-dashboard__bar-shell">
                            <div
                                class="mood-dashboard__bar mood-dashboard__bar--revenue"
                                style="height: {{ max(12, ($day['value'] / $maxWeeklyRevenue) * 100) }}%"
                            ></div>
                        </div>
                        <span class="mood-dashboard__bar-label">{{ $day['label'] }}</span>
                        <span class="mood-dashboard__bar-value">{{ number_format($day['value'], 3) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($recentOrders->isNotEmpty())
        <div class="mood-dashboard__recent">
            <div class="mood-dashboard__recent-header">
                <h3 class="mood-dashboard__recent-title">Recent orders</h3>
                <a href="{{ $ordersUrl }}" class="mood-dashboard__recent-link">View all</a>
            </div>

            <div class="mood-dashboard__table-wrap">
                <table class="mood-dashboard__table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order]) }}" class="mood-dashboard__order-link">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td>{{ $order->customer_name }}</td>
                                <td class="mood-dashboard__amount">{{ number_format((float) $order->total, 3) }} KWD</td>
                                <td>
                                    <x-filament::badge :color="$order->payment_status->getColor()">
                                        {{ $order->payment_status->getLabel() }}
                                    </x-filament::badge>
                                </td>
                                <td class="mood-dashboard__time">{{ $order->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
