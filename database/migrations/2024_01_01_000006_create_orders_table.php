<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_code')->unique();
            $table->timestamp('timestamp')->useCurrent();
            $table->uuid('created_by_id');
            $table->uuid('order_source_id');
            $table->uuid('account_id');
            $table->uuid('product_id');
            $table->uuid('product_model_id')->nullable();
            $table->uuid('product_type_id');
            $table->string('city')->nullable();
            $table->uuid('expedition_id')->nullable();
            $table->timestamp('deadline_at');
            $table->enum('complexity', ['SIMPLE', 'MEDIUM', 'COMPLEX'])->default('MEDIUM');
            $table->enum('status', ['DRAFT', 'CONFIRMED', 'DESIGN_IN_PROGRESS', 'DESIGN_APPROVED', 'IN_PRODUCTION', 'READY_TO_SHIP', 'SHIPPED', 'COMPLETED', 'CANCELLED', 'ON_HOLD'])->default('DRAFT');
            $table->enum('payment_type', ['SPL', 'COD', 'NON_COD']);
            $table->decimal('total_order', 18, 2);
            $table->enum('payment_status', ['UNPAID', 'DP', 'LUNAS'])->default('UNPAID');
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->enum('design_status', ['PROCESS', 'ACC'])->default('PROCESS');
            $table->enum('packing_type', ['BUBBLE', 'TRIPLEK', 'KAYU']);
            $table->text('product_sentence');
            $table->text('admin_notes')->nullable();
            $table->json('form_snapshot')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreign('order_source_id')->references('id')->on('order_sources');
            $table->foreign('account_id')->references('id')->on('customer_accounts');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('product_model_id')->references('id')->on('product_models');
            $table->foreign('product_type_id')->references('id')->on('product_types');
            $table->foreign('expedition_id')->references('id')->on('carriers');

            $table->index('status');
            $table->index('design_status');
            $table->index('payment_status');
            $table->index('deadline_at');
            $table->index('created_by_id');
            $table->index('account_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
