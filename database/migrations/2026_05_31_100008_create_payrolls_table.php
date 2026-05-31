<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->year('year');
            $table->tinyInteger('month');
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('tunjangan_transport', 15, 2)->default(0);
            $table->decimal('tunjangan_makan', 15, 2)->default(0);
            $table->decimal('tunjangan_jabatan', 15, 2)->default(0);
            $table->decimal('tunjangan_lainnya', 15, 2)->default(0);
            $table->decimal('lembur', 15, 2)->default(0);
            $table->decimal('reimburse', 15, 2)->default(0);
            $table->decimal('penghasilan_bruto', 15, 2)->default(0);
            $table->decimal('potongan_bpjs_kes', 15, 2)->default(0);
            $table->decimal('potongan_bpjs_tk', 15, 2)->default(0);
            $table->decimal('potongan_pph21', 15, 2)->default(0);
            $table->decimal('potongan_lainnya', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('gaji_bersih', 15, 2)->default(0);
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
