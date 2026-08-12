<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_ptkp', function (Blueprint $table) {
            $table->enum('ter_category', ['A', 'B', 'C'])->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('tax_ptkp', function (Blueprint $table) {
            $table->dropColumn('ter_category');
        });
    }
};
