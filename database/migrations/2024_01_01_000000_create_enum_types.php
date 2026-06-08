<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Removed global ENUM creation for MySQL compatibility.
        // ENUMs will be defined at the column level in subsequent migrations.
    }

    public function down(): void
    {
        // Nothing to drop globally.
    }
};
