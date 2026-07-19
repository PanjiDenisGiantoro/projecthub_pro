<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Warna kotak unit di Bagan Organisasi, bisa diatur manual per unit (kosong = otomatis per level). */
    public function up(): void
    {
        Schema::table('organization_units', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('organization_units', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
