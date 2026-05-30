<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- users.company_id (denormalized untuk fast O(1) lookup) ---
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete()->after('department_id');
        });

        // Populate dari chain: user → department → division → branch → company
        DB::statement('
            UPDATE users u
            INNER JOIN departments d  ON d.id  = u.department_id
            INNER JOIN divisions   dv ON dv.id = d.division_id
            INNER JOIN branches    b  ON b.id  = dv.branch_id
            SET u.company_id = b.company_id
            WHERE u.department_id IS NOT NULL
        ');

        // --- projects.company_id (anchor utama multi-tenancy) ---
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete()->after('id');
        });

        // Populate dari manager user's company
        DB::statement('
            UPDATE projects p
            INNER JOIN users u ON u.id = p.manager_id
            SET p.company_id = u.company_id
            WHERE u.company_id IS NOT NULL
        ');

        // Fallback: dari client user's company
        DB::statement('
            UPDATE projects p
            INNER JOIN users u ON u.id = p.client_id
            SET p.company_id = u.company_id
            WHERE p.company_id IS NULL AND u.company_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
