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
        Schema::table('milestones', function (Blueprint $table) {
            $table->timestamp('client_approved_at')->nullable()->after('status');
            $table->foreignId('client_approved_via_token_id')->nullable()->after('client_approved_at')
                ->constrained('client_portal_tokens')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_approved_via_token_id');
            $table->dropColumn('client_approved_at');
        });
    }
};
