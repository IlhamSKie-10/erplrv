<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_queues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('code', ['ADVERTISING_1', 'ADVERTISING_2', 'HOMEDECOR', 'LOGO_UKIR'])->unique();
            $table->string('name');
        });

        Schema::create('production_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('code', ['LAS', 'LASER', 'RANGKAI', 'STCR_UV', 'CD', 'FINISHING', 'BUBBLE', 'DATE'])->unique();
            $table->string('name');
            $table->integer('sort_order');
            $table->integer('default_estimated_minutes')->default(60);
            $table->boolean('requires_previous_stage')->default(true);
        });

        Schema::create('production_work_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->uuid('queue_id');
            $table->uuid('current_stage_id')->nullable();
            $table->uuid('assigned_personnel_id')->nullable();
            $table->enum('status', ['NOT_STARTED', 'STARTED', 'COMPLETED', 'BLOCKED', 'REWORK', 'DONE'])->default('NOT_STARTED');
            $table->enum('deadline_band', ['SAFE', 'H3', 'DUE_TODAY', 'OVERDUE', 'DONE'])->default('SAFE');
            $table->enum('priority_tier', ['TIER_1_OVERDUE', 'TIER_2_TODAY', 'TIER_3_H3', 'TIER_4_SAFE', 'TIER_5_DONE'])->default('TIER_4_SAFE');
            $table->double('dynamic_score')->default(0);
            $table->integer('estimated_minutes')->default(120);
            $table->integer('remaining_steps')->default(5);
            $table->integer('remaining_minutes');
            $table->enum('blocked_reason', ['WAITING_MATERIAL', 'WAITING_DESIGN', 'MACHINE_ISSUE', 'MANPOWER_ISSUE', 'CUSTOMER_REVISION', 'PREVIOUS_STAGE_INCOMPLETE', 'OTHER'])->nullable();
            $table->enum('blocked_severity', ['MINOR', 'MAJOR'])->nullable();
            $table->boolean('dependencies_met')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('pinned_expires_at')->nullable();
            $table->boolean('is_held')->default(false);
            $table->text('hold_reason')->nullable();
            $table->double('manual_sort_index')->nullable();
            $table->uuid('override_assigned_to')->nullable();
            $table->timestamp('meaningful_progress_at')->nullable();
            $table->timestamp('latest_progress_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('queue_id')->references('id')->on('production_queues');
            $table->foreign('current_stage_id')->references('id')->on('production_stages');
            $table->foreign('assigned_personnel_id')->references('id')->on('personnel');

            $table->index('queue_id');
            $table->index('status');
            $table->index(['priority_tier', 'dynamic_score']);
            $table->index('deadline_band');
        });

        Schema::create('production_progress_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('work_order_id');
            $table->uuid('stage_id');
            $table->uuid('personnel_id');
            $table->enum('status', ['NOT_STARTED', 'STARTED', 'COMPLETED', 'BLOCKED', 'REWORK', 'DONE']);
            $table->text('note')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('work_order_id')->references('id')->on('production_work_orders')->cascadeOnDelete();
            $table->foreign('stage_id')->references('id')->on('production_stages');
            $table->foreign('personnel_id')->references('id')->on('personnel');

            $table->index('work_order_id');
            $table->index('stage_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_progress_logs');
        Schema::dropIfExists('production_work_orders');
        Schema::dropIfExists('production_stages');
        Schema::dropIfExists('production_queues');
    }
};
