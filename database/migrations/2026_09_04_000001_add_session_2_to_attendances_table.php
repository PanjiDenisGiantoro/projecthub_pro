<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sesi ke-2 buat shift split (2 sesi kerja + jeda panjang di tengah, cth: kurir/outlet).
     * Kolom sesi 1 (check_in, check_out, dst) tidak berubah — shift biasa/non-split tetap
     * cuma pakai kolom sesi 1, sesi 2 selalu null.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('check_in_2')->nullable()->after('check_out');
            $table->time('check_out_2')->nullable()->after('check_in_2');
            $table->string('location_in_2')->nullable()->after('location_out');
            $table->decimal('lat_in_2', 10, 8)->nullable()->after('distance_in');
            $table->decimal('lng_in_2', 11, 8)->nullable()->after('lat_in_2');
            $table->unsignedInteger('distance_in_2')->nullable()->after('lng_in_2');
            $table->decimal('lat_out_2', 10, 8)->nullable()->after('lng_out');
            $table->decimal('lng_out_2', 11, 8)->nullable()->after('lat_out_2');
            $table->boolean('face_verified_in_2')->default(false)->after('face_verified_in');
            $table->boolean('face_verified_out_2')->default(false)->after('face_verified_out');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_2', 'check_out_2', 'location_in_2',
                'lat_in_2', 'lng_in_2', 'distance_in_2',
                'lat_out_2', 'lng_out_2',
                'face_verified_in_2', 'face_verified_out_2',
            ]);
        });
    }
};
