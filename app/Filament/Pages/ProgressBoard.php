<?php

namespace App\Filament\Pages;

use App\Enums\ProgressStatus;
use App\Models\ProductionProgressLog;
use App\Models\ProductionWorkOrder;
use Filament\Pages\Page;

class ProgressBoard extends Page
{
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.progress-board';

    // Auto-refresh setiap 10 detik
    protected static ?string $pollingInterval = '10s';

    public ?string $workOrderId = null;
    public ?string $filterStatus = null;
    public string $lastRefreshed = '';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Progress Produksi';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Produksi';
    }

    public function getTitle(): string
    {
        return 'Progress Produksi';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['PRODUCTION', 'MANAGER', 'SUPER_ADMIN', 'DEVELOPER']);
    }

    public function mount(): void
    {
        $this->lastRefreshed = now()->format('H:i:s');
    }

    public function getLogs(): \Illuminate\Database\Eloquent\Collection
    {
        $this->lastRefreshed = now()->format('H:i:s');

        return ProductionProgressLog::with(['workOrder.order', 'stage', 'personnel'])
            ->when($this->workOrderId, fn ($q) => $q->where('work_order_id', $this->workOrderId))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    }

    public function getWorkOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return ProductionWorkOrder::with('order')
            ->where('status', '!=', ProgressStatus::DONE)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }

    public function getTodayStats(): array
    {
        $today = now()->startOfDay();

        $stats = ProductionProgressLog::where('created_at', '>=', $today)
            ->selectRaw('COUNT(*) as today_logs')
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as started", [ProgressStatus::STARTED->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed", [ProgressStatus::COMPLETED->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as blocked", [ProgressStatus::BLOCKED->value])
            ->first();

        return [
            'today_logs'  => (int) ($stats->today_logs ?? 0),
            'started'     => (int) ($stats->started ?? 0),
            'completed'   => (int) ($stats->completed ?? 0),
            'blocked'     => (int) ($stats->blocked ?? 0),
        ];
    }
}
