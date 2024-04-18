<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\Services\OrderService;
use Filament\Widgets\ChartWidget;

class TasksStatusesChart extends ChartWidget
{
    protected static ?string $heading = 'مخطط حالات الطلبات';

    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $data = $this->prepareCounts();

        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                    'hoverOffset' => 1,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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

    private function prepareCounts(): array
    {
        $data = [];
        $counts = $this->getCounts();

        foreach (array_keys(OrderService::STATUSES) as $status) {
            $data[OrderService::STATUSES[$status]] = $counts[$status][0]['count'] ?? 0;
        }

        return $data;
    }
}
