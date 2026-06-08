<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\{
    ProgressStatus, DeadlineBand, PriorityTier,
    BlockedReason, BlockedSeverity
};

class ProductionWorkOrder extends Model
{
    use HasUuids;
    protected $table = 'production_work_orders';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id', 'queue_id', 'current_stage_id', 'assigned_personnel_id',
        'status', 'deadline_band', 'priority_tier', 'dynamic_score',
        'estimated_minutes', 'remaining_steps', 'remaining_minutes',
        'blocked_reason', 'blocked_severity', 'dependencies_met',
        'is_pinned', 'pinned_expires_at', 'is_held', 'hold_reason',
        'manual_sort_index', 'override_assigned_to',
        'meaningful_progress_at', 'latest_progress_at',
    ];

    protected function casts(): array
    {
        return [
            'status'               => ProgressStatus::class,
            'deadline_band'        => DeadlineBand::class,
            'priority_tier'        => PriorityTier::class,
            'blocked_reason'       => BlockedReason::class,
            'blocked_severity'     => BlockedSeverity::class,
            'dynamic_score'        => 'float',
            'estimated_minutes'    => 'integer',
            'remaining_steps'      => 'integer',
            'remaining_minutes'    => 'integer',
            'dependencies_met'     => 'boolean',
            'is_pinned'            => 'boolean',
            'is_held'              => 'boolean',
            'manual_sort_index'    => 'float',
            'pinned_expires_at'    => 'datetime',
            'meaningful_progress_at' => 'datetime',
            'latest_progress_at'   => 'datetime',
            'created_at'           => 'datetime',
            'updated_at'           => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(ProductionQueue::class, 'queue_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(ProductionStage::class, 'current_stage_id');
    }

    public function assignedPersonnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'assigned_personnel_id');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(ProductionProgressLog::class, 'work_order_id');
    }

    // ─── Helpers ─────────────────────────────────────────

    /** Returns Filament/Tailwind color string for badge rendering */
    public function deadlineBandColor(): string
    {
        return match ($this->deadline_band) {
            DeadlineBand::SAFE      => 'success',
            DeadlineBand::H3        => 'warning',
            DeadlineBand::DUE_TODAY => 'danger',
            DeadlineBand::OVERDUE   => 'danger',
            DeadlineBand::DONE      => 'gray',
            default                 => 'gray',
        };
    }

    /** Returns emoji + label for human-readable display */
    public function deadlineBandLabel(): string
    {
        return match ($this->deadline_band) {
            DeadlineBand::SAFE      => '🟢 Aman',
            DeadlineBand::H3        => '🟡 H-3',
            DeadlineBand::DUE_TODAY => '🟠 Hari Ini',
            DeadlineBand::OVERDUE   => '🔴 Terlambat',
            DeadlineBand::DONE      => '✅ Selesai',
            default                 => '-',
        };
    }

    /** Returns Tailwind CSS classes for row-level urgency coloring */
    public function deadlineBandRowClass(): string
    {
        return match ($this->deadline_band) {
            DeadlineBand::OVERDUE   => 'bg-red-50 dark:bg-red-950',
            DeadlineBand::DUE_TODAY => 'bg-orange-50 dark:bg-orange-950',
            DeadlineBand::H3        => 'bg-yellow-50 dark:bg-yellow-950',
            default                 => 'bg-white dark:bg-gray-900',
        };
    }
}
