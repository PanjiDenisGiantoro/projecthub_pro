<?php

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
        Schema::table('pph21_settings', function (Blueprint $table) {
            // gaji_pokok selalu kena pajak (tidak bisa dimatikan), komponen lain bisa dikecualikan
            $table->boolean('tax_tunjangan_jabatan')->default(true)->after('method');
            $table->boolean('tax_tunjangan_transport')->default(true)->after('tax_tunjangan_jabatan');
            $table->boolean('tax_tunjangan_makan')->default(true)->after('tax_tunjangan_transport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pph21_settings', function (Blueprint $table) {
            $table->dropColumn(['tax_tunjangan_jabatan', 'tax_tunjangan_transport', 'tax_tunjangan_makan']);
        });
    }
};
