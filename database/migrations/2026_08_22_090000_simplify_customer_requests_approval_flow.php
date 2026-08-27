<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen first so old + new status values can coexist while we remap.
        DB::statement("ALTER TABLE customer_requests MODIFY status VARCHAR(30) NOT NULL DEFAULT 'waiting_approval'");

        DB::table('customer_requests')->whereIn('status', ['submitted', 'under_review'])->update(['status' => 'waiting_approval']);
        DB::table('customer_requests')->where('status', 'in_progress')->update(['status' => 'approved']);

        DB::statement("ALTER TABLE customer_requests MODIFY status ENUM('waiting_approval','approved','rejected','done') NOT NULL DEFAULT 'waiting_approval'");

        Schema::table('customer_requests', function (Blueprint $table) {
            $table->foreignId('completed_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
        });
    }

    public function down(): void
    {
        Schema::table('customer_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn('completed_at');
        });

        DB::statement("ALTER TABLE customer_requests MODIFY status VARCHAR(30) NOT NULL DEFAULT 'submitted'");

        DB::table('customer_requests')->where('status', 'waiting_approval')->update(['status' => 'submitted']);

        DB::statement("ALTER TABLE customer_requests MODIFY status ENUM('submitted','under_review','approved','rejected','in_progress','done') NOT NULL DEFAULT 'submitted'");
    }
};
