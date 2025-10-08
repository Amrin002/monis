<?php
// database/migrations/2025_01_10_000001_add_status_kirim_to_laporans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->boolean('terkirim_ke_wali')->default(false)->after('tanggal');
            $table->timestamp('tanggal_kirim_ke_wali')->nullable()->after('terkirim_ke_wali');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropColumn(['terkirim_ke_wali', 'tanggal_kirim_ke_wali']);
        });
    }
};
