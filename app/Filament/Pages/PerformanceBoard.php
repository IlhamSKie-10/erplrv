<?php

namespace App\Filament\Pages;

use App\Enums\DeadlineBand;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProgressStatus;
use App\Models\Order;
use App\Models\Personnel;
use App\Models\ProductionProgressLog;
use App\Models\ProductionStage;
use App\Models\ProductionWorkOrder;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PerformanceBoard extends Page
{
    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.performance-board';

    // Auto-refresh setiap 30 detik
    protected static ?string $pollingInterval = '30s';

    public int $throughputDays = 7;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public static function getNavigationLabel(): string
    {
        return 'KPI & Performa';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan & Admin';
    }

    public function getTitle(): string
    {
        return 'KPI & Performa Produksi';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['MANAGER', 'SUPER_ADMIN', 'DEVELOPER']);
    }

    protected function getViewData(): array
    {
        return [
            'orderFunnel'      => $this->getOrderFunnel(),
            'deadlineStats'    => $this->getDeadlineStats(),
            'paymentStats'     => $this->getPaymentStats(),
            'throughput'       => $this->getThroughputData(),
            'personnelKpi'     => $this->getPersonnelKpi(),
            'bottleneckStages' => $this->getBottleneckStages(),
        ];
    }

    /** Order count per status (funnel) */
    private function getOrderFunnel(): array
    {
        $counts = Order::whereNull('deleted_at')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            ['label' => 'Draft',        'status' => 'DRAFT',              'count' => $counts['DRAFT'] ?? 0,              'color' => 'bg-gray-200'],
            ['label' => 'Dikonfirmasi', 'status' => 'CONFIRMED',          'count' => $counts['CONFIRMED'] ?? 0,          'color' => 'bg-blue-200'],
            ['label' => 'Desain',       'status' => 'DESIGN_IN_PROGRESS', 'count' => $counts['DESIGN_IN_PROGRESS'] ?? 0, 'color' => 'bg-purple-200'],
            ['label' => 'Produksi',     'status' => 'IN_PRODUCTION',      'count' => $counts['IN_PRODUCTION'] ?? 0,      'color' => 'bg-yellow-200'],
            ['label' => 'Siap Kirim',   'status' => 'READY_TO_SHIP',      'count' => $counts['READY_TO_SHIP'] ?? 0,      'color' => 'bg-indigo-200'],
            ['label' => 'Dikirim',      'status' => 'SHIPPED',            'count' => $counts['SHIPPED'] ?? 0,            'color' => 'bg-cyan-200'],
            ['label' => 'Selesai',      'status' => 'COMPLETED',          'count' => $counts['COMPLETED'] ?? 0,          'color' => 'bg-green-200'],
            ['label' => 'Dibatalkan',   'status' => 'CANCELLED',          'count' => $counts['CANCELLED'] ?? 0,          'color' => 'bg-red-200'],
        ];
    }

    /** WO deadline band distribution */
    private function getDeadlineStats(): array
    {
        $base = ProductionWorkOrder::where('status', '!=', ProgressStatus::DONE);
        $total = $base->count();

        return [
            'total'    => $total,
            'overdue'  => (clone $base)->where('deadline_band', DeadlineBand::OVERDUE)->count(),
            'due_today'=> (clone $base)->where('deadline_band', DeadlineBand::DUE_TODAY)->count(),
            'h3'       => (clone $base)->where('deadline_band', DeadlineBand::H3)->count(),
            'safe'     => (clone $base)->where('deadline_band', DeadlineBand::SAFE)->count(),
            'blocked'  => (clone $base)->where('status', ProgressStatus::BLOCKED)->count(),
            'rework'   => (clone $base)->where('status', ProgressStatus::REWORK)->count(),
        ];
    }

    /** Payment status breakdown */
    private function getPaymentStats(): array
    {
        $counts = Order::whereNull('deleted_at')
            ->select('payment_status', DB::raw('count(*) as total'), DB::raw('sum(total_order) as revenue'), DB::raw('sum(amount_paid) as collected'))
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        return [
            'unpaid'  => ['count' => $counts['UNPAID']?->total ?? 0,  'revenue' => $counts['UNPAID']?->revenue ?? 0],
            'dp'      => ['count' => $counts['DP']?->total ?? 0,       'revenue' => $counts['DP']?->revenue ?? 0, 'collected' => $counts['DP']?->collected ?? 0],
            'lunas'   => ['count' => $counts['LUNAS']?->total ?? 0,    'revenue' => $counts['LUNAS']?->revenue ?? 0],
            'total_revenue' => Order::whereNull('deleted_at')->sum('total_order'),
            'total_collected' => Order::whereNull('deleted_at')->sum('amount_paid'),
        ];
    }

    /** Daily throughput: orders completed per day for last N days */
    private function getThroughputData(): array
    {
        $days = $this->throughputDays;
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $data[] = [
                'date'  => $date->format('d/m'),
                'count' => ProductionProgressLog::whereDate('created_at', $date)
                    ->where('status', ProgressStatus::COMPLETED)
                    ->count(),
            ];
        }

        return $data;
    }

    /** Top 10 personnel by log count in last 30 days */
    private function getPersonnelKpi(): \Illuminate\Support\Collection
    {
        return ProductionProgressLog::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('personnel_id')
            ->select('personnel_id', DB::raw('count(*) as log_count'),
                DB::raw("sum(case when status = 'COMPLETED' then 1 else 0 end) as completed_count"),
                DB::raw("sum(case when status = 'BLOCKED' then 1 else 0 end) as blocked_count"))
            ->groupBy('personnel_id')
            ->orderByDesc('log_count')
            ->limit(10)
            ->with('personnel:id,name')
            ->get();
    }

    /** Stages with most BLOCKED logs = bottlenecks */
    private function getBottleneckStages(): \Illuminate\Support\Collection
    {
        return ProductionProgressLog::where('status', ProgressStatus::BLOCKED)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('stage_id')
            ->select('stage_id', DB::raw('count(*) as blocked_count'))
            ->groupBy('stage_id')
            ->orderByDesc('blocked_count')
            ->limit(5)
            ->with('stage:id,name')
            ->get();
    }
}
