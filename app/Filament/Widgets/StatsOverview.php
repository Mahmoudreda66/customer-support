<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $statistics = $this->getStatistics();

        return [
            Stat::make('عدد العملاء', Customer::query()->count())
                ->description($statistics['today_customers'].' عميل جديد اليوم')
                ->descriptionIcon('heroicon-m-arrow-trending-'.($statistics['today_customers'] ? 'up' : 'down'))
                ->color($statistics['today_customers'] ? 'success' : 'danger'),
            Stat::make('عدد الطلبات', Order::query()->count())
                ->description($statistics['today_orders'].' طلب جديد اليوم')
                ->descriptionIcon('heroicon-m-arrow-trending-'.($statistics['today_orders'] ? 'up' : 'down'))
                ->color($statistics['today_orders'] ? 'success' : 'danger'),
            Stat::make('عدد الفروع', Branch::query()->count())
                ->description('عدد جميع الفروع')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('success'),
        ];
    }

    private function getStatistics(): array
    {
        return [
            'today_customers' => Customer::query()->whereDate('created_at', date('Y-m-d'))->count(),
            'today_orders' => Order::query()->whereDate('created_at', date('Y-m-d'))->count(),
            'branches_count' => Branch::query()->count(),
        ];
    }
}
