<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Diisi bareng buat shift split (mis. kurir/outlet): sesi 1 = start_time..break_start_time,
            // jeda panjang, sesi 2 = break_end_time..end_time. Null berdua = shift biasa (1 sesi).
            $table->time('break_start_time')->nullable()->after('end_time');
            $table->time('break_end_time')->nullable()->after('break_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['break_start_time', 'break_end_time']);
        });
    }
};
