<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class OrderTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Pesanan 7 Hari Terakhir';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d M');
            $count = \App\Models\Order::whereDate('created_at', $date->toDateString())->count();
            $data[] = $count;
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
