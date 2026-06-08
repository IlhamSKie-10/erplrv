<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE order_sources (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE
        )');

        DB::statement('CREATE TABLE customer_accounts (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(255),
            email VARCHAR(255),
            business_priority business_priority_enum NOT NULL DEFAULT \'NORMAL\',
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            deleted_at TIMESTAMPTZ
        )');

        DB::statement('CREATE TABLE product_categories (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            deleted_at TIMESTAMPTZ
        )');

        DB::statement('CREATE TABLE product_types (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE
        )');

        DB::statement('CREATE TABLE product_models (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            product_type_id UUID NOT NULL REFERENCES product_types(id),
            code VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE
        )');

        DB::statement('CREATE TABLE products (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            category_id UUID NOT NULL REFERENCES product_categories(id),
            product_type_id UUID NOT NULL REFERENCES product_types(id),
            product_model_id UUID REFERENCES product_models(id),
            lead_time_days INT NOT NULL DEFAULT 7,
            base_production_minutes INT NOT NULL DEFAULT 120,
            production_queue production_queue_code_enum NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            deleted_at TIMESTAMPTZ
        )');

        DB::statement('CREATE TABLE carriers (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE
        )');
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
