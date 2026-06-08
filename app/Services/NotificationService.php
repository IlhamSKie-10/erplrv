<?php

namespace App\Services;

use App\Enums\ReminderStatus;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Reminder;
use App\Models\User;

class NotificationService
{
    public function sendByRole(
        string $title,
        string $body,
        string $roleCode,
        ?string $orderId = null,
        string $level = 'INFO'
    ): void {
        $users = User::where('status', 'ACTIVE')
            ->whereHas('roles', fn ($query) => $query->where('code', $roleCode))
            ->get(['id']);

        if ($users->isEmpty()) {
            return;
        }

        $notification = Notification::create([
            'title' => $title,
            'body' => $body,
            'order_id' => $orderId,
            'level' => $level,
        ]);

        foreach ($users as $user) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ]);
        }
    }

    public function getUnreadCount(string $userId): int
    {
        return NotificationRecipient::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function getNotifications(string $userId): array
    {
        $recipients = NotificationRecipient::with('notification.order')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return $recipients->map(function (NotificationRecipient $recipient) {
            $notification = $recipient->notification;

            return [
                'id' => $notification?->id ?? $recipient->id,
                'recipientId' => $recipient->id,
                'notificationId' => $recipient->notification_id,
                'title' => $notification?->title,
                'body' => $notification?->body,
                'level' => $notification?->level?->value,
                'orderId' => $notification?->order_id,
                'orderCode' => $notification?->order?->order_code,
                'read' => $recipient->read_at !== null,
                'readAt' => $recipient->read_at?->toISOString(),
                'createdAt' => $notification?->created_at?->toISOString() ?? $recipient->created_at?->toISOString(),
            ];
        })->toArray();
    }

    public function markAsRead(string $recipientId, string $userId): array
    {
        $recipient = NotificationRecipient::where('id', $recipientId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $recipient->update(['read_at' => now()]);

        return ['success' => true];
    }

    public function markAllAsRead(string $userId): array
    {
        NotificationRecipient::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ['success' => true];
    }

    public function getReminders(string $userId): array
    {
        $reminders = Reminder::with('order')
            ->where('assignee_id', $userId)
            ->orderBy('due_at', 'asc')
            ->get();

        return $reminders->map(fn (Reminder $reminder) => [
            'id' => $reminder->id,
            'title' => $reminder->title,
            'message' => $reminder->message,
            'status' => $reminder->status?->value,
            'statusKey' => strtolower($reminder->status?->value ?? ''),
            'assignee' => $reminder->assignee_id,
            'dueAt' => $reminder->due_at?->toISOString(),
            'remindAt' => $reminder->remind_at?->toISOString(),
            'level' => 'info',
            'entityLabel' => $reminder->order?->order_code ?? '-',
            'orderId' => $reminder->order_id,
            'orderCode' => $reminder->order?->order_code,
        ])->toArray();
    }

    public function acknowledgeReminder(string $reminderId, string $userId): array
    {
        Reminder::where('id', $reminderId)
            ->where('assignee_id', $userId)
            ->update(['status' => ReminderStatus::ACKNOWLEDGED->value]);

        return ['success' => true];
    }

    public function getActivityLogs(?string $orderId = null, int $limit = 200): array
    {
        $query = AuditLog::with('actorUser')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($orderId) {
            $query->where('entity_id', $orderId)->where('entity_type', 'order');
        }

        return $query->get()->map(function (AuditLog $log) {
            $actorName = $log->actorUser?->full_name ?? 'System';
            $action = $log->action?->value ?? (string) $log->action;

            return [
                'id' => (string) $log->id,
                'actorName' => $actorName,
                'userId' => $log->actor_user_id ?? 'system',
                'action' => $action,
                'entityType' => $log->entity_type,
                'entityId' => $log->entity_id,
                'summary' => $log->summary,
                'details' => $log->summary,
                'before' => $log->before,
                'after' => $log->after,
                'createdAt' => $log->created_at?->toISOString(),
                'timestamp' => $log->created_at?->toISOString(),
            ];
        })->toArray();
    }
}
