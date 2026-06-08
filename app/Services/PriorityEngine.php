<?php

namespace App\Services;

use Carbon\Carbon;
use App\Enums\{PriorityTier, BlockedSeverity, BusinessPriority};

/**
 * PHP port of lib/priority-engine.ts
 *
 * Priority logic is PRESERVED EXACTLY — do not change without explicit instruction.
 *
 * Two-pass priority system:
 *  1. PriorityTier  — absolute bucket (TIER_1 always beats TIER_2 regardless of scores)
 *  2. DynamicScore  — numeric sort within the same tier
 */
class PriorityEngine
{
    /**
     * Compute the priority tier based on deadline vs. now.
     *
     * Maps from priority-engine.ts::computePriorityTier() exactly:
     *   - DONE/COMPLETED   → TIER_5_DONE
     *   - Past (not today) → TIER_1_OVERDUE
     *   - Same day         → TIER_2_TODAY
     *   - ≤ 3 business days→ TIER_3_H3
     *   - Otherwise        → TIER_4_SAFE
     */
    public function computePriorityTier(
        Carbon $deadlineAt,
        string $status,
        ?Carbon $now = null
    ): PriorityTier {
        $now ??= Carbon::now();

        if ($status === 'DONE' || $status === 'COMPLETED') {
            return PriorityTier::TIER_5_DONE;
        }

        // Past = deadline has passed AND is not the same calendar day
        $isPast = $deadlineAt->lt($now) && !$deadlineAt->isSameDay($now);

        if ($isPast) {
            return PriorityTier::TIER_1_OVERDUE;
        }

        if ($deadlineAt->isSameDay($now)) {
            return PriorityTier::TIER_2_TODAY;
        }

        // Business days — matches date-fns differenceInBusinessDays()
        // Carbon::diffInWeekdays() counts Mon-Fri boundaries (equivalent)
        $bizDays = $now->diffInWeekdays($deadlineAt);

        if ($bizDays <= 3) {
            return PriorityTier::TIER_3_H3;
        }

        return PriorityTier::TIER_4_SAFE;
    }

    /**
     * Compute dynamic score used for within-tier sorting.
     *
     * Maps from priority-engine.ts::computeDynamicScore() exactly:
     *  1. BLOCKED status bonus
     *  2. Stale progress penalty
     *  3. Workload complexity
     *  4. Remaining workflow steps
     *  5. Business priority bonus
     *
     * @param array{
     *   status: string,
     *   blocked_severity: BlockedSeverity|null,
     *   meaningful_progress_at: Carbon|null,
     *   estimated_minutes: int,
     *   remaining_steps: int,
     *   business_priority: BusinessPriority
     * } $params
     */
    public function computeDynamicScore(array $params, ?Carbon $now = null): float
    {
        $now ??= Carbon::now();

        $status               = $params['status'];
        $blockedSeverity      = $params['blocked_severity'] ?? null;
        $meaningfulProgressAt = $params['meaningful_progress_at'] ?? null;
        $estimatedMinutes     = $params['estimated_minutes'];
        $remainingSteps       = $params['remaining_steps'];
        $businessPriority     = $params['business_priority'];

        $score = 0.0;

        // ─── 1. BLOCKED STATUS ────────────────────────────────────
        if ($status === 'BLOCKED') {
            $score += ($blockedSeverity === BlockedSeverity::MAJOR
                || ($blockedSeverity instanceof BlockedSeverity && $blockedSeverity === BlockedSeverity::MAJOR))
                ? 70
                : 30;
        }

        // ─── 2. STALE PROGRESS ───────────────────────────────────
        if ($status !== 'DONE' && $status !== 'COMPLETED') {
            $lastProgress = $meaningfulProgressAt ?? $now;
            $inactiveHours = max(0, $lastProgress->diffInHours($now));

            if ($inactiveHours > 24) {
                $score += 18;
            } elseif ($inactiveHours > 12) {
                $score += 10;
            } else {
                $score += 4; // active recent
            }
        }

        // ─── 3. WORKLOAD COMPLEXITY ───────────────────────────────
        if ($estimatedMinutes >= 300) {
            $score += 15; // Complex (> 5 hours)
        } elseif ($estimatedMinutes <= 60) {
            $score += 5;  // Simple (≤ 1 hour)
        } else {
            $score += 10; // Medium
        }

        // ─── 4. REMAINING WORKFLOW ────────────────────────────────
        $score += $remainingSteps * 2;

        // ─── 5. BUSINESS PRIORITY ────────────────────────────────
        $bpValue = $businessPriority instanceof BusinessPriority
            ? $businessPriority->value
            : (string) $businessPriority;

        $bpMap = [
            'VIP'           => 20,
            'STRATEGIC'     => 15,
            'CORPORATE'     => 10,
            'REPEAT_CLIENT' => 5,
            'NORMAL'        => 0,
        ];

        $score += $bpMap[$bpValue] ?? 0;

        return $score;
    }
}
