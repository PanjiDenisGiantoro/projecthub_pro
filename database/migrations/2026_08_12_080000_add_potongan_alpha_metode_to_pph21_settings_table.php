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
            $table->enum('potongan_alpha_metode', ['proporsional', 'nominal'])->default('proporsional')->after('potong_alpha');
            $table->decimal('potongan_alpha_nominal', 15, 2)->default(0)->after('potongan_alpha_metode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pph21_settings', function (Blueprint $table) {
            $table->dropColumn(['potongan_alpha_metode', 'potongan_alpha_nominal']);
        });
    }
};
