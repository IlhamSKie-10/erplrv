<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DesignTask;
use App\Models\Personnel;
use App\Models\ProductionQueue;
use App\Models\ProductionStage;
use App\Models\ProductionWorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DesignService
{
    public function __construct(
        private readonly PriorityEngine $priorityEngine,
    ) {
    }

    public function getDesignTasks(): array
    {
        $tasks = DesignTask::with([
            'order.account',
            'order.product',
            'order.productType',
            'order.orderSource',
            'assignedDesigner',
        ])
            ->whereHas('order', fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('updated_at', 'desc')
            ->get();

        return $tasks->map(fn (DesignTask $task) => [
            'id' => $task->id,
            'orderId' => $task->order_id,
            'orderCode' => $task->order?->order_code,
            'accountName' => $task->order?->account?->name ?? '',
            'orderSource' => $task->order?->orderSource?->code ?? '',
            'productId' => $task->order?->product?->code ?? '',
            'productSentence' => $task->order?->product_sentence ?? '',
            'adminNotes' => $task->order?->admin_notes ?? '',
            'queueId' => $this->mapQueueCode($task->order?->product?->production_queue?->value),
            'status' => $task->status?->value ?? 'PROCESS',
            'designAccAt' => \Carbon\Carbon::parse($task->getRawOriginal('design_acc_at') ?? null)?->toISOString(),
            'assignedDesigner' => $task->assignedDesigner?->full_name ?? '',
            'printSticker' => $task->print_sticker ?? 'REQUIRED_LATER',
            'cutMethods' => array_values($task->cut_methods ?? []),
            'deadlineAt' => $task->order?->deadline_at?->toISOString(),
            'orderStatus' => $task->order?->status?->value,
            'forwardedAt' => $task->forwarded_at?->toISOString(),
            'updatedAt' => $task->updated_at?->toISOString(),
        ])->toArray();
    }

    public function updateDesignTask(string $taskId, array $input, string $actorUserId): array
    {
        return DB::transaction(function () use ($taskId, $input, $actorUserId) {
            $task = DesignTask::with('order')->lockForUpdate()->findOrFail($taskId);
            $order = $task->order;

            $previousStatus = $task->status?->value;
            $newStatus = array_key_exists('status', $input)
                ? $this->normalizeDesignStatus($input['status'])
                : null;

            $isTransitionToAcc = $newStatus === 'ACC' && $previousStatus !== 'ACC';
            $isRevertingAcc = $newStatus === 'PROCESS' && $previousStatus === 'ACC';

            if ($isRevertingAcc && ProductionWorkOrder::where('order_id', $order->id)->exists()) {
                throw new \RuntimeException(
                    'Tidak bisa membatalkan ACC - pesanan sudah diteruskan ke produksi.'
                );
            }

            $updateData = [];

            if ($newStatus !== null) {
                $updateData['status'] = $newStatus;
            }

            $printStickerInput = $input['print_sticker'] ?? $input['printSticker'] ?? null;
            if ($printStickerInput !== null) {
                $updateData['print_sticker'] = $this->normalizePrintSticker($printStickerInput);
            }

            if (array_key_exists('cut_methods', $input) || array_key_exists('cutMethods', $input)) {
                $updateData['cut_methods'] = $this->normalizeCutMethods($input['cut_methods'] ?? $input['cutMethods'] ?? []);
            }

            $assignedDesignerInput = $input['assigned_designer_id']
                ?? $input['assignedDesigner']
                ?? $input['assigned_designer']
                ?? null;
            $assignedDesignerId = $this->resolvePersonnelId($assignedDesignerInput);
            if ($assignedDesignerId) {
                $updateData['assigned_designer_id'] = $assignedDesignerId;
            }

            $designAccAtInput = $input['design_acc_at'] ?? $input['designAccAt'] ?? null;
            if ($isTransitionToAcc) {
                $updateData['design_acc_at'] = $designAccAtInput ? Carbon::parse($designAccAtInput) : now();
            } elseif ($isRevertingAcc) {
                $updateData['design_acc_at'] = null;
            } elseif ($designAccAtInput !== null) {
                $updateData['design_acc_at'] = Carbon::parse($designAccAtInput);
            }

            if ($updateData !== []) {
                $task->update($updateData);
            }

            if ($newStatus !== null && $newStatus !== $previousStatus) {
                if ($newStatus === 'ACC') {
                    $order->update([
                        'design_status' => 'ACC',
                        'status' => 'DESIGN_APPROVED',
                    ]);
                } else {
                    $order->update([
                        'design_status' => 'PROCESS',
                        'status' => 'DESIGN_IN_PROGRESS',
                    ]);
                }
            }

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'design',
                'entity_id' => $taskId,
                'action' => 'UPDATE',
                'summary' => "Updated design task for order {$order->order_code}",
            ]);

            return ['success' => true];
        }, 10);
    }

    public function forwardToProduction(string $taskId, string $actorUserId): array
    {
        return DB::transaction(function () use ($taskId, $actorUserId) {
            $task = DesignTask::with(['order.product', 'order.account'])->lockForUpdate()->findOrFail($taskId);
            $now = now();

            $task->update(['forwarded_at' => $now]);

            $order = $task->order;

            if (ProductionWorkOrder::where('order_id', $order->id)->exists()) {
                throw new \RuntimeException('Sudah diteruskan ke produksi.');
            }

            $queueCode = $order->product?->production_queue?->value ?? 'ADVERTISING_1';
            $queue = ProductionQueue::where('code', $queueCode)->first() ?? ProductionQueue::firstOrCreate(
                ['code' => $queueCode],
                ['name' => str_replace('_', ' ', $queueCode)]
            );
            $firstStage = ProductionStage::orderBy('sort_order')->first() ?? ProductionStage::firstOrCreate(
                ['code' => 'LASER'],
                ['name' => 'Laser', 'sort_order' => 1]
            );

            $deadlineAt = $order->deadline_at ?? $now->copy()->addDays(7);
            $remainingMinutes = max(0, $now->diffInMinutes($deadlineAt, false));
            $estimatedMinutes = $order->product?->base_production_minutes
                ?? $firstStage->default_estimated_minutes
                ?? 120;

            $initialTier = $this->priorityEngine->computePriorityTier($deadlineAt, 'NOT_STARTED', $now);
            $initialScore = $this->priorityEngine->computeDynamicScore([
                'status' => 'NOT_STARTED',
                'blocked_severity' => null,
                'meaningful_progress_at' => $now,
                'estimated_minutes' => $estimatedMinutes,
                'remaining_steps' => 5,
                'business_priority' => $order->account?->business_priority ?? 'NORMAL',
            ], $now);

            ProductionWorkOrder::create([
                'order_id' => $order->id,
                'queue_id' => $queue->id,
                'current_stage_id' => $firstStage->id,
                'status' => 'NOT_STARTED',
                'remaining_minutes' => $remainingMinutes,
                'priority_tier' => $initialTier->value,
                'dynamic_score' => $initialScore,
                'estimated_minutes' => $estimatedMinutes,
                'remaining_steps' => 5,
            ]);

            $order->update(['status' => 'IN_PRODUCTION']);

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'design',
                'entity_id' => $taskId,
                'action' => 'FORWARD',
                'summary' => "Forwarded {$order->order_code} to production",
            ]);

            // Filament Native Database Notification
            $productionTeam = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('code', ['PRODUCTION', 'MANAGER']))->get();
            if ($productionTeam->isNotEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->title('Masuk Produksi')
                    ->body("Pesanan {$order->order_code} telah diteruskan ke bengkel.")
                    ->success()
                    ->sendToDatabase($productionTeam);
            }

            return ['success' => true];
        }, 10);
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

    private function normalizeDesignStatus(mixed $status): ?string
    {
        if (!is_string($status) || trim($status) === '') {
            return null;
        }

        return strtoupper($status) === 'ACC' ? 'ACC' : 'PROCESS';
    }

    private function normalizePrintSticker(mixed $value): string
    {
        if (!is_string($value)) {
            return 'NO';
        }

        return match (strtoupper($value)) {
            'YES' => 'YES',
            'REQUIRED_LATER' => 'REQUIRED_LATER',
            default => 'NO',
        };
    }

    private function normalizeCutMethods(mixed $methods): array
    {
        if (!is_array($methods)) {
            return [];
        }

        $normalized = [];

        foreach ($methods as $method) {
            if (!is_string($method) || trim($method) === '') {
                continue;
            }

            $key = strtoupper(str_replace('-', '_', $method));
            $normalized[] = match ($key) {
                'CNC' => 'CNC',
                'LASER' => 'LASER',
                'OUTSOURCE' => 'OUTSOURCE',
                default => 'NONE',
            };
        }

        return array_values(array_unique($normalized));
    }

    private function resolvePersonnelId(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return Personnel::where('id', $value)->value('id')
            ?? Personnel::where('full_name', $value)->value('id');
    }
}
