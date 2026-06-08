<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Performance reports
        DB::statement('CREATE TABLE performance_reports (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            period_label VARCHAR(255) NOT NULL,
            subject_type VARCHAR(255) NOT NULL,
            subject_id VARCHAR(255) NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            completed_jobs INT NOT NULL,
            delayed_jobs INT NOT NULL,
            blocked_minutes INT NOT NULL,
            metrics JSONB NOT NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX pr_subject_type_id_idx ON performance_reports(subject_type, subject_id)');
        DB::statement('CREATE INDEX pr_created_at_desc_idx ON performance_reports(created_at DESC)');

        // Audit log — BigInt PK (as in Prisma)
        DB::statement('CREATE TABLE audit_logs (
            id BIGSERIAL PRIMARY KEY,
            actor_user_id UUID,
            entity_type VARCHAR(255) NOT NULL,
            entity_id VARCHAR(255),
            action audit_action_enum NOT NULL,
            summary TEXT NOT NULL,
            before JSONB,
            after JSONB,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )');

        DB::statement('CREATE INDEX al_entity_type_id_idx ON audit_logs(entity_type, entity_id)');
        DB::statement('CREATE INDEX al_actor_user_id_idx ON audit_logs(actor_user_id)');
        DB::statement('CREATE INDEX al_created_at_desc_idx ON audit_logs(created_at DESC)');

        // Shift calendar
        DB::statement('CREATE TABLE shift_calendars (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            date DATE UNIQUE NOT NULL,
            is_workday BOOLEAN NOT NULL DEFAULT TRUE,
            working_hours INT NOT NULL DEFAULT 8
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_calendars');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('performance_reports');
    }
};
