<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            // Must drop FK before unique index in MySQL
            $table->dropForeign(['company_id']);
            $table->dropUnique(['company_id', 'code']);
            $table->dropColumn('company_id');

            // Add branch_id
            $table->foreignId('branch_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'code']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');

            $table->foreignId('company_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['company_id', 'code']);
        });
    }
};
