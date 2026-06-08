<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayOrders = \App\Models\Order::whereDate('created_at', today())->count();
        $monthOrders = \App\Models\Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalRevenue = \App\Models\Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');

        $overdueOrders = \App\Models\Order::where('status', '!=', \App\Enums\OrderStatus::COMPLETED)
            ->where('deadline_at', '<', now())
            ->count();

        return [
            Stat::make('Total Order', "{$todayOrders} Hari Ini")
                ->description("{$monthOrders} order bulan ini")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make('Pendapatan (Bulan Ini)', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Total Amount Paid')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Pesanan Terlambat', $overdueOrders)
                ->description('Melewati batas deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueOrders > 0 ? 'danger' : 'success'),
        ];
    }
}
