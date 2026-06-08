<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indonesia_regions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // 'regency' or 'district'
            $table->string('label', 200)->index(); // Full searchable label
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indonesia_regions');
    }
};
