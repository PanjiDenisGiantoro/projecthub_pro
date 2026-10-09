<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master tarif PPN per periode — tarif berlaku pada tanggal transaksi
        // dipilih dari baris yang start_date <= tanggal <= end_date (end_date null = masih berlaku).
        Schema::create('ppn_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 5, 2); // persen, mis. 11.00
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        DB::table('ppn_rates')->insert([
            'rate'       => 11,
            'start_date' => '2022-04-01',
            'end_date'   => null,
            'notes'      => 'UU HPP — PPN 11% sejak 1 April 2022',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ppn_rates');
    }
};
