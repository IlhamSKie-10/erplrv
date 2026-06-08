<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Users table — preserves all Prisma User columns
        // Adds: password (Laravel auth), remember_token (Laravel session)
        // Preserves: id (UUID), auth_user_id, email, full_name, status, created_at, updated_at, deleted_at
        DB::statement('CREATE TABLE users (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            auth_user_id UUID UNIQUE,
            email VARCHAR(255) UNIQUE NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL DEFAULT \'\',
            status user_status_enum NOT NULL DEFAULT \'ACTIVE\',
            remember_token VARCHAR(100),
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            deleted_at TIMESTAMPTZ
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
