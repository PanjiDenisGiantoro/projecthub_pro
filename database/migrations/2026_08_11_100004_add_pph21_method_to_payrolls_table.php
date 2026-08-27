<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Metode yang dipakai saat payroll ini digenerate — dicatat per baris supaya
            // rekonsiliasi Desember (TER) bisa menjumlahkan potongan riil Jan-Nov dengan akurat
            // walau setting perusahaan berubah di tengah tahun berjalan.
            $table->string('pph21_method', 10)->nullable()->after('potongan_pph21');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('pph21_method');
        });
    }
};
