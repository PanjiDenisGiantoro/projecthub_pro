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
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('max_users')->nullable()->after('duration_days'); // null = custom/unlimited
            $table->string('icon', 30)->nullable()->after('cta_type'); // key ikon header, mis. paper-plane, rocket, crown
            $table->string('color', 20)->nullable()->after('icon'); // key warna tema kolom, mis. blue, green, purple
            $table->string('fitur_text')->nullable()->after('color'); // teks baris "Fitur" di tabel perbandingan
            $table->string('hris_feature')->nullable()->after('fitur_text'); // teks baris "Fitur HRIS"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['max_users', 'icon', 'color', 'fitur_text', 'hris_feature']);
        });
    }
};
