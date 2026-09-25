<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('milestones', 'code')) {
                $table->string('code', 50)->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('milestones', 'priority')) {
                $table->string('priority', 30)->default('low')->after('assigned_to');
            }
            if (!Schema::hasColumn('milestones', 'release_target')) {
                $table->string('release_target', 100)->nullable()->after('priority');
            }
        });

        Schema::table('sprints', function (Blueprint $table) {
            if (!Schema::hasColumn('sprints', 'code')) {
                $table->string('code', 50)->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('sprints', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('milestone_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('sprints', 'priority')) {
                $table->string('priority', 30)->default('normal')->after('assigned_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropColumn(['code', 'priority', 'release_target']);
        });

        Schema::table('sprints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['code', 'priority']);
        });
    }
};
