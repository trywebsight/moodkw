<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class DashboardStats extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dashboard-stats';

    protected function getViewData(): array
    {
        $today = now()->startOfDay();

        $totalOrders = Order::query()->count();
        $todayOrders = Order::query()->where('created_at', '>=', $today)->count();
        $paidOrders = Order::query()->where('payment_status', PaymentStatus::Paid)->count();
        $pendingPayments = Order::query()->where('payment_status', PaymentStatus::Pending)->count();
        $revenue = (float) Order::query()->where('payment_status', PaymentStatus::Paid)->sum('total');
        $todayRevenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('paid_at', '>=', $today)
            ->sum('total');

        $weeklyOrders = $this->weeklySeries('orders');
        $weeklyRevenue = $this->weeklySeries('revenue');
        $maxWeeklyOrders = max(1, $weeklyOrders->max('value'));
        $maxWeeklyRevenue = max(1, $weeklyRevenue->max('value'));

        $recentOrders = Order::query()
            ->with(['governorate', 'area'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'totalOrders' => $totalOrders,
            'todayOrders' => $todayOrders,
            'paidOrders' => $paidOrders,
            'pendingPayments' => $pendingPayments,
            'revenue' => $revenue,
            'todayRevenue' => $todayRevenue,
            'weeklyOrders' => $weeklyOrders,
            'weeklyRevenue' => $weeklyRevenue,
            'maxWeeklyOrders' => $maxWeeklyOrders,
            'maxWeeklyRevenue' => $maxWeeklyRevenue,
            'recentOrders' => $recentOrders,
            'ordersUrl' => OrderResource::getUrl('index'),
            'paidOrdersUrl' => OrderResource::getUrl('index', [
                'tableFilters' => ['payment_status' => ['value' => PaymentStatus::Paid->value]],
            ]),
            'pendingOrdersUrl' => OrderResource::getUrl('index', [
                'tableFilters' => ['payment_status' => ['value' => PaymentStatus::Pending->value]],
            ]),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: float|int}>
     */
    private function weeklySeries(string $type): Collection
    {
        return collect(range(6, 0))->map(function (int $daysAgo) use ($type): array {
            $start = now()->subDays($daysAgo)->startOfDay();
            $end = $start->copy()->addDay();

            if ($type === 'revenue') {
                $value = (float) Order::query()
                    ->where('payment_status', PaymentStatus::Paid)
                    ->where('paid_at', '>=', $start)
                    ->where('paid_at', '<', $end)
                    ->sum('total');
            } else {
                $value = Order::query()
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<', $end)
                    ->count();
            }

            return [
                'label' => $start->format('D'),
                'value' => $value,
            ];
        });
    }
}
