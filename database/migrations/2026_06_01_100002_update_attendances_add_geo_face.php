<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('lat_in', 10, 8)->nullable()->after('location_in');
            $table->decimal('lng_in', 11, 8)->nullable()->after('lat_in');
            $table->unsignedInteger('distance_in')->nullable()->after('lng_in');
            $table->decimal('lat_out', 10, 8)->nullable()->after('location_out');
            $table->decimal('lng_out', 11, 8)->nullable()->after('lat_out');
            $table->boolean('face_verified_in')->default(false)->after('photo_in');
            $table->boolean('face_verified_out')->default(false)->after('face_verified_in');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'lat_in', 'lng_in', 'distance_in',
                'lat_out', 'lng_out',
                'face_verified_in', 'face_verified_out',
            ]);
        });
    }
};
