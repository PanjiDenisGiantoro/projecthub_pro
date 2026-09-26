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
        if (Schema::hasTable('board_columns') && !Schema::hasColumn('board_columns', 'icon')) {
            Schema::table('board_columns', function (Blueprint $table) {
                $table->string('icon', 50)->nullable()->after('color')->default('clipboard');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('board_columns') && Schema::hasColumn('board_columns', 'icon')) {
            Schema::table('board_columns', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
