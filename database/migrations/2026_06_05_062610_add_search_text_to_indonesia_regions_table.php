<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indonesia_regions', function (Blueprint $table) {
            // Stores lowercase words without prefixes (Kecamatan, Kota, Kabupaten)
            // for fast keyword-based searching
            $table->string('search_text', 300)->nullable()->after('label');
            $table->index('search_text');
        });
    }

    public function down(): void
    {
        Schema::table('indonesia_regions', function (Blueprint $table) {
            $table->dropColumn('search_text');
        });
    }
};
