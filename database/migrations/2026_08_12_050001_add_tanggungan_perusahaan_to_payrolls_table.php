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
        Schema::table('payrolls', function (Blueprint $table) {
            // Tunjangan PPh 21 (skema gross_up) — masuk Pendapatan, besarnya sama dengan potongan_pph21.
            $table->decimal('tunjangan_pph21', 15, 2)->default(0)->after('potongan_pph21');
            // Biaya yang ditanggung perusahaan, tidak mengurangi gaji bersih karyawan.
            $table->decimal('tanggungan_bpjs_kes', 15, 2)->default(0)->after('gaji_bersih');
            $table->decimal('tanggungan_bpjs_tk', 15, 2)->default(0)->after('tanggungan_bpjs_kes');
            $table->decimal('tanggungan_pph21', 15, 2)->default(0)->after('tanggungan_bpjs_tk');
            $table->decimal('total_tanggungan_perusahaan', 15, 2)->default(0)->after('tanggungan_pph21');
            // Disimpan per baris payroll (mirror pph21_method) supaya slip lama tetap konsisten kalau setting company berubah belakangan.
            $table->string('pph21_scheme')->default('gross')->after('pph21_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'tunjangan_pph21',
                'tanggungan_bpjs_kes',
                'tanggungan_bpjs_tk',
                'tanggungan_pph21',
                'total_tanggungan_perusahaan',
                'pph21_scheme',
            ]);
        });
    }
};
