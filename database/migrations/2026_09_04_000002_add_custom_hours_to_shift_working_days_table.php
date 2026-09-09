<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jam kerja custom per hari (opsional) — buat kasus shift yang jam kerjanya beda
     * di hari tertentu (mis. Senin-Jumat 08:00-17:00, tapi Sabtu cuma 08:00-12:00).
     * Null = hari itu ikut start_time/end_time default di tabel shifts.
     */
    public function up(): void
    {
        Schema::table('shift_working_days', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('day_of_week');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('shift_working_days', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
