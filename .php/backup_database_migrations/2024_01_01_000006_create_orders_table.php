<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Main orders table — preserves ALL Prisma Order columns exactly
        DB::statement('CREATE TABLE orders (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            order_code VARCHAR(255) UNIQUE NOT NULL,
            timestamp TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            created_by_id UUID NOT NULL REFERENCES users(id),
            order_source_id UUID NOT NULL REFERENCES order_sources(id),
            account_id UUID NOT NULL REFERENCES customer_accounts(id),
            product_id UUID NOT NULL REFERENCES products(id),
            product_model_id UUID REFERENCES product_models(id),
            product_type_id UUID NOT NULL REFERENCES product_types(id),
            city VARCHAR(255),
            expedition_id UUID REFERENCES carriers(id),
            deadline_at TIMESTAMPTZ NOT NULL,
            complexity job_complexity_enum NOT NULL DEFAULT \'MEDIUM\',
            status order_status_enum NOT NULL DEFAULT \'DRAFT\',
            payment_type payment_type_enum NOT NULL,
            total_order DECIMAL(18,2) NOT NULL,
            payment_status payment_status_enum NOT NULL DEFAULT \'UNPAID\',
            amount_paid DECIMAL(18,2) NOT NULL DEFAULT 0,
            design_status design_status_enum NOT NULL DEFAULT \'PROCESS\',
            packing_type packing_type_enum NOT NULL,
            product_sentence TEXT NOT NULL,
            admin_notes TEXT,
            form_snapshot JSONB,
            version INT NOT NULL DEFAULT 1,
            submitted_at TIMESTAMPTZ,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            deleted_at TIMESTAMPTZ
        )');

        // Indexes matching Prisma @@index directives
        DB::statement('CREATE INDEX orders_status_idx ON orders(status)');
        DB::statement('CREATE INDEX orders_design_status_idx ON orders(design_status)');
        DB::statement('CREATE INDEX orders_payment_status_idx ON orders(payment_status)');
        DB::statement('CREATE INDEX orders_deadline_at_idx ON orders(deadline_at)');
        DB::statement('CREATE INDEX orders_created_by_id_idx ON orders(created_by_id)');
        DB::statement('CREATE INDEX orders_account_id_idx ON orders(account_id)');
        DB::statement('CREATE INDEX orders_created_at_desc_idx ON orders(created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
