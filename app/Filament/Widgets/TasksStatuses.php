<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\Services\OrderService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Collection;

class TasksStatuses extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        $counts = $this->getCounts();
        $statuses = array_keys(OrderService::STATUSES);
        $charts = [];

        foreach ($statuses as $status) {
            $charts[] = Stat::make(OrderService::STATUSES[$status], $counts[$status][0]['count'] ?? 0);
        }

        return $charts;
    }

    public function getCounts(): array
    {
        return Order::query()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) AS count')
            ->get()
            ->groupBy('status')
            ->toArray();
    }
}
