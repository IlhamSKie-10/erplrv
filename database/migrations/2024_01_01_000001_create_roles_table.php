<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('code', ['SUPER_ADMIN', 'CS', 'DESIGNER', 'PRODUCTION', 'MANAGER', 'DEVELOPER'])->unique();
            $table->string('name');
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
