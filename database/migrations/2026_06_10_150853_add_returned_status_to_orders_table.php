<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('DRAFT','CONFIRMED','DESIGN_IN_PROGRESS','DESIGN_APPROVED','IN_PRODUCTION','READY_TO_SHIP','SHIPPED','COMPLETED','CANCELLED','ON_HOLD','RETURNED') NOT NULL DEFAULT 'DRAFT'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting the enum back by removing 'RETURNED'. Note: If there are existing records with 'RETURNED', this might fail.
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('DRAFT','CONFIRMED','DESIGN_IN_PROGRESS','DESIGN_APPROVED','IN_PRODUCTION','READY_TO_SHIP','SHIPPED','COMPLETED','CANCELLED','ON_HOLD') NOT NULL DEFAULT 'DRAFT'");
    }
};
