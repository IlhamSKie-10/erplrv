<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('auth_user_id')->unique();
            $table->string('email')->unique();
            $table->string('full_name');
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'SUSPENDED'])->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
