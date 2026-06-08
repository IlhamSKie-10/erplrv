<?php

namespace App\Filament\Pages;

use App\Enums\DeadlineBand;
use App\Enums\ProgressStatus;
use App\Models\ProductionQueue;
use App\Models\ProductionWorkOrder;
use Filament\Pages\Page;

class PriorityBoard extends Page
{
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.priority-board';

    // Auto-refresh setiap 10 detik
    protected static ?string $pollingInterval = '10s';

    public ?string $activeQueue = null;

    public string $lastRefreshed = '';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    public static function getNavigationLabel(): string
    {
        return 'Priority Board';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Produksi';
    }

    public function getTitle(): string
    {
        return 'Priority Board';
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

    public function getJobs(): \Illuminate\Database\Eloquent\Collection
    {
        $this->lastRefreshed = now()->format('H:i:s');

        return ProductionWorkOrder::with(['order.account', 'order.product', 'currentStage', 'assignedPersonnel'])
            ->when($this->activeQueue, fn ($q) => $q->where('queue_id', $this->activeQueue))
            ->where('status', '!=', ProgressStatus::DONE)
            ->orderBy('dynamic_score', 'desc')
            ->get();
    }

    public function getQueues(): \Illuminate\Database\Eloquent\Collection
    {
        return ProductionQueue::orderBy('name')->get();
    }

    /** Summary stats for the top bar */
    public function getStats(): array
    {
        $base = ProductionWorkOrder::where('status', '!=', ProgressStatus::DONE);

        return [
            'total'     => $base->count(),
            'overdue'   => (clone $base)->where('deadline_band', DeadlineBand::OVERDUE)->count(),
            'due_today' => (clone $base)->where('deadline_band', DeadlineBand::DUE_TODAY)->count(),
            'h3'        => (clone $base)->where('deadline_band', DeadlineBand::H3)->count(),
            'blocked'   => (clone $base)->where('status', ProgressStatus::BLOCKED)->count(),
            'held'      => (clone $base)->where('is_held', true)->count(),
        ];
    }
}
