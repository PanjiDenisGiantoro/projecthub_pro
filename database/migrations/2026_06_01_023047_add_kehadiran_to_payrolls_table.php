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
            $table->tinyInteger('hari_kerja')->default(0)->after('reimburse');
            $table->tinyInteger('hari_hadir')->default(0)->after('hari_kerja');
            $table->tinyInteger('hari_cuti')->default(0)->after('hari_hadir');
            $table->tinyInteger('hari_alpha')->default(0)->after('hari_cuti');
            $table->decimal('potongan_alpha', 15, 2)->default(0)->after('hari_alpha');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['hari_kerja', 'hari_hadir', 'hari_cuti', 'hari_alpha', 'potongan_alpha']);
        });
    }
};
