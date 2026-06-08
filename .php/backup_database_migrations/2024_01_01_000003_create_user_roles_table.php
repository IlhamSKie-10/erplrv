<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE user_roles (
            user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role_id UUID NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            assigned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            PRIMARY KEY (user_id, role_id)
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
