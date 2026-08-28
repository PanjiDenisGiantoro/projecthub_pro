<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE overtimes MODIFY status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('overtimes', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        DB::statement("ALTER TABLE overtimes MODIFY status ENUM('pending', 'approved', 'rejected', 'processed') NOT NULL DEFAULT 'pending'");
    }
};
