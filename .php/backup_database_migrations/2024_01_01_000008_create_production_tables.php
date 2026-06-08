<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Production queue and stage lookup tables
        DB::statement('CREATE TABLE production_queues (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code production_queue_code_enum UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL
        )');

        DB::statement('CREATE TABLE production_stages (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code production_stage_code_enum UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL,
            default_estimated_minutes INT NOT NULL DEFAULT 60,
            requires_previous_stage BOOLEAN NOT NULL DEFAULT TRUE
        )');

        // Main production work order — preserves all Prisma ProductionWorkOrder fields
        DB::statement('CREATE TABLE production_work_orders (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            order_id UUID UNIQUE NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            queue_id UUID NOT NULL REFERENCES production_queues(id),
            current_stage_id UUID REFERENCES production_stages(id),
            assigned_personnel_id UUID REFERENCES personnel(id),
            status progress_status_enum NOT NULL DEFAULT \'NOT_STARTED\',
            deadline_band deadline_band_enum NOT NULL DEFAULT \'SAFE\',

            -- Priority System
            priority_tier priority_tier_enum NOT NULL DEFAULT \'TIER_4_SAFE\',
            dynamic_score DOUBLE PRECISION NOT NULL DEFAULT 0,

            -- Workload
            estimated_minutes INT NOT NULL DEFAULT 120,
            remaining_steps INT NOT NULL DEFAULT 5,
            remaining_minutes INT NOT NULL,

            -- Blocks & Dependencies
            blocked_reason blocked_reason_enum,
            blocked_severity blocked_severity_enum,
            dependencies_met BOOLEAN NOT NULL DEFAULT TRUE,

            -- Manual Override
            is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
            pinned_expires_at TIMESTAMPTZ,
            is_held BOOLEAN NOT NULL DEFAULT FALSE,
            hold_reason TEXT,
            manual_sort_index DOUBLE PRECISION,
            override_assigned_to UUID,

            -- Tracking
            meaningful_progress_at TIMESTAMPTZ,
            latest_progress_at TIMESTAMPTZ,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX pwo_queue_id_idx ON production_work_orders(queue_id)');
        DB::statement('CREATE INDEX pwo_status_idx ON production_work_orders(status)');
        DB::statement('CREATE INDEX pwo_priority_idx ON production_work_orders(priority_tier ASC, dynamic_score DESC)');
        DB::statement('CREATE INDEX pwo_deadline_band_idx ON production_work_orders(deadline_band)');
        DB::statement('CREATE INDEX pwo_order_id_idx ON production_work_orders(order_id)');

        // Progress log
        DB::statement('CREATE TABLE production_progress_logs (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            work_order_id UUID NOT NULL REFERENCES production_work_orders(id) ON DELETE CASCADE,
            stage_id UUID NOT NULL REFERENCES production_stages(id),
            personnel_id UUID NOT NULL REFERENCES personnel(id),
            status progress_status_enum NOT NULL,
            note TEXT,
            started_at TIMESTAMPTZ,
            completed_at TIMESTAMPTZ,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX ppl_work_order_id_idx ON production_progress_logs(work_order_id)');
        DB::statement('CREATE INDEX ppl_stage_id_idx ON production_progress_logs(stage_id)');
        DB::statement('CREATE INDEX ppl_created_at_desc_idx ON production_progress_logs(created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('production_progress_logs');
        Schema::dropIfExists('production_work_orders');
        Schema::dropIfExists('production_stages');
        Schema::dropIfExists('production_queues');
    }
};
