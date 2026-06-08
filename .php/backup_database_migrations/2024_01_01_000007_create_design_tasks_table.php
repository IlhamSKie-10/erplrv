<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // design_tasks — preserves Prisma DesignTask exactly
        // cut_methods uses JSONB array (maps CutMethod[] from Prisma)
        DB::statement('CREATE TABLE design_tasks (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            assigned_designer_id UUID REFERENCES personnel(id),
            status design_task_status_enum NOT NULL DEFAULT \'PROCESS\',
            design_acc_at TIMESTAMPTZ,
            forwarded_at TIMESTAMPTZ,
            print_sticker print_sticker_option_enum NOT NULL DEFAULT \'REQUIRED_LATER\',
            cut_methods JSONB NOT NULL DEFAULT \'[]\',
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX design_tasks_status_idx ON design_tasks(status)');
        DB::statement('CREATE INDEX design_tasks_order_id_idx ON design_tasks(order_id)');
        DB::statement('CREATE INDEX design_tasks_assigned_designer_id_idx ON design_tasks(assigned_designer_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('design_tasks');
    }
};
