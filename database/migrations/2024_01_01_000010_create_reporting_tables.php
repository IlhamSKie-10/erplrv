<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('period_label');
            $table->string('subject_type');
            $table->string('subject_id');
            $table->decimal('score', 5, 2);
            $table->integer('completed_jobs');
            $table->integer('delayed_jobs');
            $table->integer('blocked_minutes');
            $table->json('metrics');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); // BigInt PK
            $table->uuid('actor_user_id')->nullable();
            $table->string('entity_type');
            $table->string('entity_id')->nullable();
            $table->enum('action', ['SIGN_IN', 'CREATE', 'UPDATE', 'SUBMIT', 'APPROVE', 'FORWARD', 'STATUS_CHANGE', 'SOFT_DELETE']);
            $table->text('summary');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('actor_user_id');
            $table->index('created_at');
        });

        Schema::create('shift_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date')->unique();
            $table->boolean('is_workday')->default(true);
            $table->integer('working_hours')->default(8);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_calendars');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('performance_reports');
    }
};
