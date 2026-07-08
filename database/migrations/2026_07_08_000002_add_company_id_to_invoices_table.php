<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete()->after('id');
        });

        // Populate dari company milik project terkait (termasuk project yang sudah soft-deleted).
        DB::statement('
            UPDATE invoices i
            INNER JOIN projects p ON p.id = i.project_id
            SET i.company_id = p.company_id
            WHERE p.company_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
