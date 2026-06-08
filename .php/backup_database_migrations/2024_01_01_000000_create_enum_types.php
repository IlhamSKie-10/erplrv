<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create all PostgreSQL native ENUMs used across the schema
        // Using raw SQL because Laravel's enum() uses varchar checks, not native PG enums
        $enums = [
            'user_status_enum'             => ['ACTIVE', 'INACTIVE', 'SUSPENDED'],
            'blocked_severity_enum'        => ['MINOR', 'MAJOR'],
            'blocked_reason_enum'          => ['WAITING_MATERIAL', 'WAITING_DESIGN', 'MACHINE_ISSUE', 'MANPOWER_ISSUE', 'CUSTOMER_REVISION', 'PREVIOUS_STAGE_INCOMPLETE', 'OTHER'],
            'job_complexity_enum'          => ['SIMPLE', 'MEDIUM', 'COMPLEX'],
            'business_priority_enum'       => ['NORMAL', 'REPEAT_CLIENT', 'CORPORATE', 'VIP', 'STRATEGIC'],
            'priority_tier_enum'           => ['TIER_1_OVERDUE', 'TIER_2_TODAY', 'TIER_3_H3', 'TIER_4_SAFE', 'TIER_5_DONE'],
            'role_code_enum'               => ['SUPER_ADMIN', 'CS', 'DESIGNER', 'PRODUCTION', 'MANAGER', 'DEVELOPER'],
            'order_status_enum'            => ['DRAFT', 'CONFIRMED', 'DESIGN_IN_PROGRESS', 'DESIGN_APPROVED', 'IN_PRODUCTION', 'READY_TO_SHIP', 'SHIPPED', 'COMPLETED', 'CANCELLED', 'ON_HOLD'],
            'payment_type_enum'            => ['SPL', 'COD', 'NON_COD'],
            'payment_status_enum'          => ['UNPAID', 'DP', 'LUNAS'],
            'design_status_enum'           => ['PROCESS', 'ACC'],
            'reminder_status_enum'         => ['PENDING', 'ACKNOWLEDGED', 'DONE', 'CANCELLED'],
            'notification_level_enum'      => ['INFO', 'WARNING', 'CRITICAL'],
            'production_queue_code_enum'   => ['ADVERTISING_1', 'ADVERTISING_2', 'HOMEDECOR', 'LOGO_UKIR'],
            'production_stage_code_enum'   => ['LAS', 'LASER', 'RANGKAI', 'STCR_UV', 'CD', 'FINISHING', 'BUBBLE', 'DATE'],
            'progress_status_enum'         => ['NOT_STARTED', 'STARTED', 'COMPLETED', 'BLOCKED', 'REWORK', 'DONE'],
            'deadline_band_enum'           => ['SAFE', 'H3', 'DUE_TODAY', 'OVERDUE', 'DONE'],
            'design_task_status_enum'      => ['PROCESS', 'ACC'],
            'cut_method_enum'              => ['NONE', 'CNC', 'LASER', 'OUTSOURCE'],
            'print_sticker_option_enum'    => ['YES', 'NO', 'REQUIRED_LATER'],
            'packing_type_enum'            => ['BUBBLE', 'TRIPLEK', 'KAYU'],
            'audit_action_enum'            => ['SIGN_IN', 'CREATE', 'UPDATE', 'SUBMIT', 'APPROVE', 'FORWARD', 'STATUS_CHANGE', 'SOFT_DELETE'],
        ];

        foreach ($enums as $name => $values) {
            $quotedValues = implode(', ', array_map(fn($v) => "'$v'", $values));
            DB::statement("DO $$ BEGIN
                CREATE TYPE {$name} AS ENUM ({$quotedValues});
            EXCEPTION WHEN duplicate_object THEN null;
            END $$;");
        }
    }

    public function down(): void
    {
        $enums = [
            'user_status_enum', 'blocked_severity_enum', 'blocked_reason_enum',
            'job_complexity_enum', 'business_priority_enum', 'priority_tier_enum',
            'role_code_enum', 'order_status_enum', 'payment_type_enum',
            'payment_status_enum', 'design_status_enum', 'reminder_status_enum',
            'notification_level_enum', 'production_queue_code_enum', 'production_stage_code_enum',
            'progress_status_enum', 'deadline_band_enum', 'design_task_status_enum',
            'cut_method_enum', 'print_sticker_option_enum', 'packing_type_enum', 'audit_action_enum',
        ];
        foreach ($enums as $enum) {
            DB::statement("DROP TYPE IF EXISTS {$enum} CASCADE;");
        }
    }
};
