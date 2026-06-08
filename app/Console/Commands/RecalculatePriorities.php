<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionWorkOrder;
use App\Services\PriorityEngine;
use Carbon\Carbon;

/**
 * RecalculatePriorities — PHP port of src/actions/priority-cron.ts
 *
 * Behavior PRESERVED EXACTLY:
 *  - Only processes active jobs (not DONE or COMPLETED)
 *  - Only writes to DB if score actually changed (> 0.01 delta)
 *  - Auto-expires pinned jobs past pinnedExpiresAt
 */
class RecalculatePriorities extends Command
{
    protected $signature   = 'priorities:recalculate';
    protected $description = 'Recalculate priority scores for all active production work orders';

    public function __construct(private readonly PriorityEngine $priorityEngine)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = Carbon::now();

        $activeJobs = ProductionWorkOrder::with(['order.account'])
            ->whereNotIn('status', ['DONE', 'COMPLETED'])
            ->get();

        $updatedCount = 0;

        foreach ($activeJobs as $job) {
            $order      = $job->order;
            $deadlineAt = $order?->deadline_at ?? $now->copy()->addDays(7);

            $newTier  = $this->priorityEngine->computePriorityTier($deadlineAt, $job->status?->value, $now);
            $newScore = $this->priorityEngine->computeDynamicScore([
                'status'               => $job->status?->value,
                'blocked_severity'     => $job->blocked_severity,
                'meaningful_progress_at' => $job->meaningful_progress_at,
                'estimated_minutes'    => $job->estimated_minutes,
                'remaining_steps'      => $job->remaining_steps,
                'business_priority'    => $order?->account?->business_priority ?? 'NORMAL',
            ], $now);

            // Only update if changed — preserves original TS optimization
            if ($newTier !== $job->priority_tier || abs($newScore - (float) $job->dynamic_score) > 0.01) {
                $job->update([
                    'priority_tier' => $newTier->value,
                    'dynamic_score' => $newScore,
                ]);
                $updatedCount++;
            }

            // Auto-expire pinned jobs
            if ($job->is_pinned && $job->pinned_expires_at && $now->gt($job->pinned_expires_at)) {
                $job->update([
                    'is_pinned'        => false,
                    'pinned_expires_at'=> null,
                    'manual_sort_index'=> null,
                ]);
            }
        }

        $this->info("Recalculated priorities. Updated: {$updatedCount} / {$activeJobs->count()} active jobs.");

        return Command::SUCCESS;
    }
}
