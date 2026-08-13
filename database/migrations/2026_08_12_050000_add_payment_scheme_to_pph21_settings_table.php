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
            // gross: karyawan tanggung penuh. gross_up: perusahaan kasih tunjangan pajak (masuk pendapatan & potongan).
            // net: perusahaan tanggung penuh pajaknya (masuk Tanggungan Perusahaan, tidak menyentuh gaji karyawan).
            $table->enum('payment_scheme', ['gross', 'gross_up', 'net'])->default('gross')->after('method');
            // Tarif JKK (Jaminan Kecelakaan Kerja) employer, bervariasi 0,24%-1,74% sesuai kelas risiko perusahaan.
            $table->decimal('jkk_rate', 6, 4)->default(0.0024)->after('payment_scheme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pph21_settings', function (Blueprint $table) {
            $table->dropColumn(['payment_scheme', 'jkk_rate']);
        });
    }
};
