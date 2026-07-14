<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete()->after('id');
        });

        // Populate dari company project terkait dulu (kalau ada)...
        DB::statement('
            UPDATE campaigns c
            INNER JOIN projects p ON p.id = c.project_id
            SET c.company_id = p.company_id
            WHERE p.company_id IS NOT NULL
        ');

        // ...sisanya (campaign tanpa project) dari company pembuatnya.
        DB::statement('
            UPDATE campaigns c
            INNER JOIN users u ON u.id = c.created_by
            SET c.company_id = u.company_id
            WHERE c.company_id IS NULL AND u.company_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
