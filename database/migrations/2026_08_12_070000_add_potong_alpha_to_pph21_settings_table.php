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
        Schema::table('pph21_settings', function (Blueprint $table) {
            $table->boolean('potong_alpha')->default(true)->after('jkk_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pph21_settings', function (Blueprint $table) {
            $table->dropColumn('potong_alpha');
        });
    }
};
