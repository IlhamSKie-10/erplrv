<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->enum('business_priority', ['NORMAL', 'REPEAT_CLIENT', 'CORPORATE', 'VIP', 'STRATEGIC'])->default('NORMAL');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('product_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_type_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            
            $table->foreign('product_type_id')->references('id')->on('product_types')->cascadeOnDelete();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->uuid('category_id');
            $table->uuid('product_type_id');
            $table->uuid('product_model_id')->nullable();
            $table->integer('lead_time_days')->default(3);
            $table->integer('base_production_minutes')->default(120);
            $table->enum('production_queue', ['ADVERTISING_1', 'ADVERTISING_2', 'HOMEDECOR', 'LOGO_UKIR'])->default('ADVERTISING_1');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('product_categories');
            $table->foreign('product_type_id')->references('id')->on('product_types');
            $table->foreign('product_model_id')->references('id')->on('product_models');
        });

        Schema::create('carriers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_models');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('customer_accounts');
        Schema::dropIfExists('order_sources');
    }
};
