<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('assigned_designer_id')->nullable();
            $table->enum('status', ['PROCESS', 'ACC'])->default('PROCESS');
            $table->timestamp('design_acc_at')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->enum('print_sticker', ['YES', 'NO', 'REQUIRED_LATER'])->default('REQUIRED_LATER');
            $table->json('cut_methods')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('assigned_designer_id')->references('id')->on('personnel');

            $table->index('status');
            $table->index('order_id');
            $table->index('assigned_designer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_tasks');
    }
};
