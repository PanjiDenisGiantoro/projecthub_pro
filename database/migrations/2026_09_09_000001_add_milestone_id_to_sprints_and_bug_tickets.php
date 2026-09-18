<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->foreignId('milestone_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });

        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->foreignId('milestone_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('milestone_id');
        });

        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('milestone_id');
        });
    }
};
