<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class OrderTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Grafik Pesanan Masuk';
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '250px';

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari Ini',
            'week' => '7 Hari Terakhir',
            'month' => '1 Bulan Terakhir',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $data = [];
        $labels = [];

        if ($activeFilter === 'today') {
            // 24 jam hari ini
            for ($i = 0; $i <= 23; $i++) {
                $time = today()->addHours($i);
                $labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                if ($time > now()->endOfHour()) {
                    $data[] = 0;
                } else {
                    $start = $time->copy()->startOfHour();
                    $end = $time->copy()->endOfHour();
                    $data[] = \App\Models\Order::whereBetween('created_at', [$start, $end])->count();
                }
            }
        } elseif ($activeFilter === 'month') {
            // 30 hari terakhir
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('d/m');
                $data[] = \App\Models\Order::whereDate('created_at', $date->toDateString())->count();
            }
        } else {
            // default: 7 hari terakhir
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('d M');
                $data[] = \App\Models\Order::whereDate('created_at', $date->toDateString())->count();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan Masuk',
                    'data' => $data,
                    'borderColor' => '#3b82f6', // Tailwind blue-500
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
