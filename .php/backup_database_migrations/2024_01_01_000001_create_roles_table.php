<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');
            $table->string('description')->nullable();
            DB::statement('ALTER TABLE roles ADD COLUMN code role_code_enum UNIQUE NOT NULL');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
