<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_requests', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete()->after('id');
        });

        // project_id selalu terisi, jadi backfill dari company project terkait cukup.
        DB::statement('
            UPDATE customer_requests cr
            INNER JOIN projects p ON p.id = cr.project_id
            SET cr.company_id = p.company_id
            WHERE p.company_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('customer_requests', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
