<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable();
            $table->uuid('assignee_id');
            $table->string('title');
            $table->text('message');
            $table->enum('status', ['PENDING', 'ACKNOWLEDGED', 'DONE', 'CANCELLED'])->default('PENDING');
            $table->timestamp('due_at');
            $table->timestamp('remind_at');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('assignee_id')->references('id')->on('users');

            $table->index('assignee_id');
            $table->index('status');
            $table->index('due_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable();
            $table->string('title');
            $table->text('body');
            $table->enum('level', ['INFO', 'WARNING', 'CRITICAL'])->default('INFO');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders');

            $table->index('created_at');
        });

        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('notification_id');
            $table->uuid('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('notification_id')->references('id')->on('notifications')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reminders');
    }
};
