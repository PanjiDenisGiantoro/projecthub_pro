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
        Schema::table('users', function (Blueprint $table) {
            // Tanggal mulai kerja — dasar hitung masa kerja untuk THR pro-rata &
            // eligibilitas cuti tahunan (12 bulan) di iterasi berikutnya.
            $table->date('hire_date')->nullable()->after('outsourcing_company_name');
            // Hanya relevan untuk tipe non-Tetap (PKWT/kontrak wajib punya tanggal
            // akhir menurut UU Ketenagakerjaan; outsourcing/harian/lainnya opsional).
            $table->date('contract_end_date')->nullable()->after('hire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['hire_date', 'contract_end_date']);
        });
    }
};
