<?php

namespace App\Services;

use App\Enums\BlockedReason;
use App\Enums\BlockedSeverity;
use App\Models\AuditLog;
use App\Models\Personnel;
use App\Models\ProductionProgressLog;
use App\Models\ProductionStage;
use App\Models\ProductionWorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function __construct(
        private readonly PriorityEngine $priorityEngine,
    ) {
    }

    public function getProductionJobs(?string $queueCode = null): array
    {
        $normalizedQueueCode = $this->normalizeQueueCode($queueCode);
        $now = Carbon::now();

        $query = ProductionWorkOrder::with([
            'order.account',
            'order.orderSource',
            'order.product',
            'order.productType',
            'order.productModel',
            'queue',
            'currentStage',
            'assignedPersonnel',
        ])->whereHas('order', fn ($builder) => $builder->whereNull('deleted_at'));

        if ($normalizedQueueCode) {
            $query->whereHas('queue', fn ($builder) => $builder->where('code', $normalizedQueueCode));
        }

        $workOrders = $query
            ->orderByRaw('is_pinned DESC, priority_tier ASC, dynamic_score DESC')
            ->get();

        foreach ($workOrders as $workOrder) {
            if ($workOrder->is_pinned && $workOrder->pinned_expires_at && $now->gt($workOrder->pinned_expires_at)) {
                $workOrder->update([
                    'is_pinned' => false,
                    'pinned_expires_at' => null,
                    'manual_sort_index' => null,
                ]);
                $workOrder->refresh();
            }
        }

        return $workOrders->map(fn (ProductionWorkOrder $workOrder) => $this->formatWorkOrder($workOrder))->toArray();
    }

    public function updateProductionStatus(string $workOrderId, array $input, string $actorUserId): array
    {
        return DB::transaction(function () use ($workOrderId, $input, $actorUserId) {
            $workOrder = ProductionWorkOrder::with('order.account')->lockForUpdate()->findOrFail($workOrderId);
            $now = Carbon::now();

            $updateData = [
                'latest_progress_at' => $now,
            ];

            $status = $this->normalizeProgressStatus($input['status'] ?? null);
            if ($status !== null) {
                $updateData['status'] = $status;
            }

            $stageId = $this->resolveStageId(
                $input['current_stage_id'] ?? $input['currentStageId'] ?? null
            );
            if ($stageId) {
                $updateData['current_stage_id'] = $stageId;
            }

            $assignedPersonnelId = $this->resolvePersonnelId(
                $input['assigned_personnel_id'] ?? $input['assignedTo'] ?? null
            );
            if ($assignedPersonnelId) {
                $updateData['assigned_personnel_id'] = $assignedPersonnelId;
            }

            if (array_key_exists('blocked_reason', $input)) {
                $updateData['blocked_reason'] = $this->normalizeBlockedReason($input['blocked_reason']);
            }

            if (array_key_exists('blocked_severity', $input)) {
                $updateData['blocked_severity'] = $this->normalizeBlockedSeverity($input['blocked_severity']);
            }

            if (array_key_exists('dependencies_met', $input)) {
                $updateData['dependencies_met'] = (bool) $input['dependencies_met'];
            }

            if (array_key_exists('is_held', $input)) {
                $updateData['is_held'] = (bool) $input['is_held'];
            }

            if (array_key_exists('hold_reason', $input)) {
                $updateData['hold_reason'] = $input['hold_reason'];
            }

            if (array_key_exists('remaining_steps', $input) && $input['remaining_steps'] !== null) {
                $updateData['remaining_steps'] = (int) $input['remaining_steps'];
            }

            $workOrder->update($updateData);
            $workOrder->refresh();

            $this->recalculatePriorityForJob($workOrder, $now);

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'production',
                'entity_id' => $workOrderId,
                'action' => 'STATUS_CHANGE',
                'summary' => "Updated production {$workOrderId}",
            ]);

            return ['success' => true];
        }, 10);
    }

    public function addProgressLog(string $workOrderId, array $input, string $actorUserId): array
    {
        return DB::transaction(function () use ($workOrderId, $input, $actorUserId) {
            $workOrder = ProductionWorkOrder::with('order.account')->lockForUpdate()->findOrFail($workOrderId);
            $now = Carbon::now();

            $stageInput = $input['stage_id'] ?? $input['stageId'] ?? $input['current_stage_id'] ?? null;
            $stageId = $this->resolveStageId($stageInput);
            if (!$stageId) {
                throw new \RuntimeException('Stage tidak ditemukan.');
            }

            $stage = ProductionStage::find($stageId);
            if (!$stage) {
                throw new \RuntimeException('Stage tidak ditemukan.');
            }

            $personInput = $input['person'] ?? $input['assignedTo'] ?? $input['personnel_id'] ?? null;
            $personnelId = $this->resolvePersonnelId($personInput);
            if (!$personnelId) {
                throw new \RuntimeException('Personnel tidak ditemukan.');
            }

            $personnel = Personnel::find($personnelId);
            $status = $this->normalizeProgressStatus($input['status'] ?? null);
            if (!$status) {
                throw new \RuntimeException('Status produksi tidak valid.');
            }

            ProductionProgressLog::create([
                'work_order_id' => $workOrderId,
                'stage_id' => $stageId,
                'personnel_id' => $personnelId,
                'status' => $status,
                'note' => $input['note'] ?? null,
                'started_at' => $status === 'STARTED'
                    ? ($input['started_at'] ?? $now)
                    : ($input['started_at'] ?? null),
                'completed_at' => in_array($status, ['COMPLETED', 'DONE'], true) ? $now : null,
            ]);

            $workOrder->update([
                'status' => $status,
                'current_stage_id' => $stageId,
                'assigned_personnel_id' => $personnelId,
                'latest_progress_at' => $now,
            ]);
            $workOrder->refresh();

            $this->recalculatePriorityForJob($workOrder, $now);

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'production',
                'entity_id' => $workOrderId,
                'action' => 'STATUS_CHANGE',
                'summary' => "Progress updated: {$status} at {$stage->code->value} by {$personnel?->full_name}",
            ]);

            return ['success' => true];
        }, 10);
    }

    public function pinJob(string $workOrderId, ?string $expiresAt, float $manualSortIndex, string $actorUserId): array
    {
        return DB::transaction(function () use ($workOrderId, $expiresAt, $manualSortIndex, $actorUserId) {
            $parsedExpiresAt = $expiresAt ? Carbon::parse($expiresAt) : now()->addDay();

            ProductionWorkOrder::where('id', $workOrderId)->update([
                'is_pinned' => true,
                'pinned_expires_at' => $parsedExpiresAt,
                'manual_sort_index' => $manualSortIndex,
            ]);

            $hours = max(1, (int) now()->diffInHours($parsedExpiresAt, false));

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'production',
                'entity_id' => $workOrderId,
                'action' => 'UPDATE',
                'summary' => "Pinned job for {$hours}h",
            ]);

            return ['success' => true];
        }, 3);
    }

    public function holdJob(string $workOrderId, bool $held, ?string $reason, string $actorUserId): array
    {
        return DB::transaction(function () use ($workOrderId, $held, $reason, $actorUserId) {
            ProductionWorkOrder::where('id', $workOrderId)->update([
                'is_held' => $held,
                'hold_reason' => $held ? $reason : null,
            ]);

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'production',
                'entity_id' => $workOrderId,
                'action' => 'UPDATE',
                'summary' => $held
                    ? 'Put job on hold: ' . ($reason ?: 'No reason provided')
                    : 'Removed job from hold',
            ]);

            return ['success' => true];
        }, 3);
    }

    public function markDependency(string $workOrderId, bool $dependenciesMet, string $actorUserId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($workOrderId, $dependenciesMet, $actorUserId, $reason) {
            $blockedReason = $dependenciesMet
                ? null
                : ($this->normalizeBlockedReason($reason) ?? BlockedReason::PREVIOUS_STAGE_INCOMPLETE->value);

            ProductionWorkOrder::where('id', $workOrderId)->update([
                'dependencies_met' => $dependenciesMet,
                'blocked_reason' => $blockedReason,
                'blocked_severity' => $dependenciesMet ? null : BlockedSeverity::MAJOR->value,
            ]);

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'production',
                'entity_id' => $workOrderId,
                'action' => 'UPDATE',
                'summary' => $dependenciesMet
                    ? 'Marked dependencies as met'
                    : 'Marked dependencies unmet: ' . $blockedReason,
            ]);

            return ['success' => true];
        }, 3);
    }

    public function getProgressLogs(?string $workOrderId = null): array
    {
        $query = ProductionProgressLog::with([
            'workOrder.order',
            'workOrder.queue',
            'stage',
            'personnel',
        ])->orderBy('created_at', 'desc');

        if ($workOrderId) {
            $query->where('work_order_id', $workOrderId);
        }

        return $query->get()->map(function (ProductionProgressLog $log) {
            $stageCode = $log->stage?->code?->value;
            $queueCode = $log->workOrder?->queue?->code?->value;

            return [
                'id' => $log->id,
                'workOrderId' => $log->work_order_id,
                'jobId' => $log->work_order_id,
                'orderCode' => $log->workOrder?->order?->order_code,
                'queueId' => $this->mapQueueCode($queueCode),
                'stageCode' => $stageCode,
                'stageId' => $this->mapStageCode($stageCode),
                'stageName' => $log->stage?->name,
                'personnelName' => $log->personnel?->full_name,
                'person' => $log->personnel?->full_name,
                'status' => $log->status?->value,
                'note' => $log->note,
                'timestamp' => $log->created_at?->toISOString(),
                'startedAt' => $log->started_at?->toISOString(),
                'completedAt' => $log->completed_at?->toISOString(),
                'createdAt' => $log->created_at?->toISOString(),
            ];
        })->toArray();
    }

    public function getPerformanceReports(): array
    {
        $logs = ProductionProgressLog::with([
            'personnel',
            'workOrder.order',
        ])->get();

        $grouped = [];
        $now = Carbon::now();

        foreach ($logs as $log) {
            $personName = $log->personnel?->full_name ?? 'Unknown';
            $key = $log->personnel_id ?: $personName;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'personnelId' => $log->personnel_id,
                    'person' => $personName,
                    'completedJobs' => 0,
                    'delayedJobs' => 0,
                    'blockedMinutes' => 0,
                ];
            }

            $status = $log->status?->value;

            if (in_array($status, ['COMPLETED', 'DONE'], true)) {
                $grouped[$key]['completedJobs']++;

                $deadlineAt = $log->workOrder?->order?->deadline_at;
                $completedAt = $log->completed_at ?? $log->created_at;
                if ($deadlineAt && $completedAt && $completedAt->gt($deadlineAt)) {
                    $grouped[$key]['delayedJobs']++;
                }
            }

            if ($status === 'BLOCKED') {
                $startedAt = $log->started_at ?? $log->created_at ?? $now;
                $blockedMinutes = min(480, max(0, $startedAt->diffInMinutes($now)));
                $grouped[$key]['blockedMinutes'] += $blockedMinutes;
            }
        }

        $reports = [];
        $index = 0;

        foreach ($grouped as $entry) {
            $onTimeRate = $entry['completedJobs'] > 0
                ? (int) round((($entry['completedJobs'] - $entry['delayedJobs']) / $entry['completedJobs']) * 100)
                : 100;

            $score = max(
                0,
                100 - ($entry['delayedJobs'] * 5) - (int) floor($entry['blockedMinutes'] / 60) * 2
            );

            $reports[] = [
                'id' => 'perf-' . $index,
                'person' => $entry['person'],
                'personnelId' => $entry['personnelId'],
                'personnelName' => $entry['person'],
                'completedJobs' => $entry['completedJobs'],
                'delayedJobs' => $entry['delayedJobs'],
                'blockedMinutes' => $entry['blockedMinutes'],
                'onTimeRate' => $onTimeRate,
                'score' => $score,
            ];
            $index++;
        }

        usort($reports, fn (array $left, array $right) => $right['score'] <=> $left['score']);

        return $reports;
    }

    private function recalculatePriorityForJob(ProductionWorkOrder $workOrder, Carbon $now): void
    {
        $deadlineAt = $workOrder->order?->deadline_at ?? $now->copy()->addDays(7);

        $newTier = $this->priorityEngine->computePriorityTier(
            $deadlineAt,
            $workOrder->status?->value ?? 'NOT_STARTED',
            $now
        );

        $newScore = $this->priorityEngine->computeDynamicScore([
            'status' => $workOrder->status?->value ?? 'NOT_STARTED',
            'blocked_severity' => $workOrder->blocked_severity,
            'meaningful_progress_at' => $workOrder->meaningful_progress_at,
            'estimated_minutes' => $workOrder->estimated_minutes ?? 120,
            'remaining_steps' => $workOrder->remaining_steps ?? 0,
            'business_priority' => $workOrder->order?->account?->business_priority ?? 'NORMAL',
        ], $now);

        $currentTier = $workOrder->priority_tier?->value ?? $workOrder->priority_tier;
        $currentScore = (float) $workOrder->dynamic_score;

        if ($currentTier !== $newTier->value || abs($newScore - $currentScore) > 0.01) {
            $workOrder->update([
                'priority_tier' => $newTier->value,
                'dynamic_score' => $newScore,
            ]);
        }
    }

    private function formatWorkOrder(ProductionWorkOrder $workOrder): array
    {
        $queueCode = $workOrder->queue?->code?->value;
        $stageCode = $workOrder->currentStage?->code?->value;

        return [
            'id' => $workOrder->id,
            'orderId' => $workOrder->order_id,
            'orderCode' => $workOrder->order?->order_code,
            'accountName' => $workOrder->order?->account?->name ?? '',
            'productSentence' => $workOrder->order?->product_sentence ?? '',
            'productType' => $workOrder->order?->productType?->name ?? '',
            'orderSource' => $workOrder->order?->orderSource?->code ?? '',
            'queueCode' => $queueCode,
            'queueId' => $this->mapQueueCode($queueCode),
            'deadlineAt' => $workOrder->order?->deadline_at?->toISOString(),
            'status' => $workOrder->status?->value ?? 'NOT_STARTED',
            'latestProgressAt' => $workOrder->latest_progress_at?->toISOString() ?? $workOrder->created_at?->toISOString(),
            'currentStage' => $stageCode,
            'currentStageId' => $this->mapStageCode($stageCode),
            'assignedTo' => $workOrder->assignedPersonnel?->full_name ?? '',
            'priorityTier' => $workOrder->priority_tier?->value ?? $workOrder->priority_tier,
            'dynamicScore' => (float) $workOrder->dynamic_score,
            'estimatedMinutes' => $workOrder->estimated_minutes,
            'remainingSteps' => $workOrder->remaining_steps,
            'remainingMinutes' => $workOrder->remaining_minutes,
            'blockedReason' => $workOrder->blocked_reason?->value ?? $workOrder->blocked_reason,
            'blockedSeverity' => $workOrder->blocked_severity?->value ?? $workOrder->blocked_severity,
            'isPinned' => (bool) $workOrder->is_pinned,
            'pinnedExpiresAt' => $workOrder->pinned_expires_at?->toISOString(),
            'dependenciesMet' => (bool) $workOrder->dependencies_met,
            'isHeld' => (bool) $workOrder->is_held,
            'holdReason' => $workOrder->hold_reason,
            'manualSortIndex' => $workOrder->manual_sort_index,
            'createdAt' => $workOrder->created_at?->toISOString(),
        ];
    }

    private function normalizeQueueCode(?string $queueCode): ?string
    {
        if (!$queueCode) {
            return null;
        }

        return match ($queueCode) {
            'advertising-1', 'ADVERTISING_1' => 'ADVERTISING_1',
            'advertising-2', 'ADVERTISING_2' => 'ADVERTISING_2',
            'homedecor', 'HOMEDECOR' => 'HOMEDECOR',
            'logo-ukir', 'LOGO_UKIR' => 'LOGO_UKIR',
            default => $queueCode,
        };
    }

    private function normalizeProgressStatus(mixed $status): ?string
    {
        if (!is_string($status) || trim($status) === '') {
            return null;
        }

        return match (strtoupper(str_replace('-', '_', $status))) {
            'STARTED' => 'STARTED',
            'COMPLETED' => 'COMPLETED',
            'BLOCKED' => 'BLOCKED',
            'REWORK' => 'REWORK',
            'DONE' => 'DONE',
            default => 'NOT_STARTED',
        };
    }

    private function normalizeStageCode(mixed $stage): ?string
    {
        if (!is_string($stage) || trim($stage) === '') {
            return null;
        }

        return match (strtolower($stage)) {
            'las' => 'LAS',
            'laser' => 'LASER',
            'rangkai' => 'RANGKAI',
            'stcr-uv', 'stcr_uv' => 'STCR_UV',
            'cd' => 'CD',
            'finishing' => 'FINISHING',
            'bubble' => 'BUBBLE',
            'date' => 'DATE',
            default => strtoupper($stage),
        };
    }

    private function mapQueueCode(?string $code): string
    {
        return match ($code) {
            'ADVERTISING_1' => 'advertising-1',
            'ADVERTISING_2' => 'advertising-2',
            'HOMEDECOR' => 'homedecor',
            'LOGO_UKIR' => 'logo-ukir',
            default => 'advertising-1',
        };
    }

    private function mapStageCode(?string $code): string
    {
        return match ($code) {
            'LAS' => 'las',
            'LASER' => 'laser',
            'RANGKAI' => 'rangkai',
            'STCR_UV' => 'stcr-uv',
            'CD' => 'cd',
            'FINISHING' => 'finishing',
            'BUBBLE' => 'bubble',
            'DATE' => 'date',
            default => 'las',
        };
    }

    private function resolveStageId(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return ProductionStage::where('id', $value)->value('id')
            ?? ProductionStage::where('code', $this->normalizeStageCode($value))->value('id');
    }

    private function resolvePersonnelId(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return Personnel::where('id', $value)->value('id')
            ?? Personnel::where('full_name', $value)->value('id');
    }

    private function normalizeBlockedReason(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtoupper(trim($value));
        foreach (BlockedReason::cases() as $reason) {
            if ($reason->value === $normalized) {
                return $reason->value;
            }
        }

        return null;
    }

    private function normalizeBlockedSeverity(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtoupper(trim($value));
        foreach (BlockedSeverity::cases() as $severity) {
            if ($severity->value === $normalized) {
                return $severity->value;
            }
        }

        return null;
    }
}
