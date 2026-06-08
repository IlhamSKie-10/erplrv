<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reminders
        DB::statement('CREATE TABLE reminders (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            order_id UUID REFERENCES orders(id),
            assignee_id UUID NOT NULL REFERENCES users(id),
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status reminder_status_enum NOT NULL DEFAULT \'PENDING\',
            due_at TIMESTAMPTZ NOT NULL,
            remind_at TIMESTAMPTZ NOT NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX reminders_assignee_id_idx ON reminders(assignee_id)');
        DB::statement('CREATE INDEX reminders_status_idx ON reminders(status)');
        DB::statement('CREATE INDEX reminders_due_at_idx ON reminders(due_at)');

        // Notifications
        DB::statement('CREATE TABLE notifications (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            order_id UUID REFERENCES orders(id),
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            level notification_level_enum NOT NULL DEFAULT \'INFO\',
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX notifications_created_at_desc_idx ON notifications(created_at DESC)');

        // Notification recipients (fan-out table)
        DB::statement('CREATE TABLE notification_recipients (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            notification_id UUID NOT NULL REFERENCES notifications(id) ON DELETE CASCADE,
            user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            read_at TIMESTAMPTZ,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX nr_user_id_read_at_idx ON notification_recipients(user_id, read_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reminders');
    }
};
