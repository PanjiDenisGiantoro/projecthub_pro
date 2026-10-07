<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_messages') && !Schema::hasColumn('project_messages', 'is_pinned')) {
            Schema::table('project_messages', function (Blueprint $table) {
                $table->boolean('is_pinned')->default(false)->after('body');
                $table->foreignId('pinned_by')->nullable()->after('is_pinned')->constrained('users')->nullOnDelete();
                $table->timestamp('pinned_at')->nullable()->after('pinned_by');
            });
        }

        if (Schema::hasTable('forum_messages') && !Schema::hasColumn('forum_messages', 'is_pinned')) {
            Schema::table('forum_messages', function (Blueprint $table) {
                $table->boolean('is_pinned')->default(false)->after('body');
                $table->foreignId('pinned_by')->nullable()->after('is_pinned')->constrained('users')->nullOnDelete();
                $table->timestamp('pinned_at')->nullable()->after('pinned_by');
            });
        }

        if (Schema::hasTable('direct_messages') && !Schema::hasColumn('direct_messages', 'is_pinned')) {
            Schema::table('direct_messages', function (Blueprint $table) {
                $table->boolean('is_pinned')->default(false)->after('body');
                $table->foreignId('pinned_by')->nullable()->after('is_pinned')->constrained('users')->nullOnDelete();
                $table->timestamp('pinned_at')->nullable()->after('pinned_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_messages') && Schema::hasColumn('project_messages', 'is_pinned')) {
            Schema::table('project_messages', function (Blueprint $table) {
                $table->dropForeign(['pinned_by']);
                $table->dropColumn(['is_pinned', 'pinned_by', 'pinned_at']);
            });
        }

        if (Schema::hasTable('forum_messages') && Schema::hasColumn('forum_messages', 'is_pinned')) {
            Schema::table('forum_messages', function (Blueprint $table) {
                $table->dropForeign(['pinned_by']);
                $table->dropColumn(['is_pinned', 'pinned_by', 'pinned_at']);
            });
        }

        if (Schema::hasTable('direct_messages') && Schema::hasColumn('direct_messages', 'is_pinned')) {
            Schema::table('direct_messages', function (Blueprint $table) {
                $table->dropForeign(['pinned_by']);
                $table->dropColumn(['is_pinned', 'pinned_by', 'pinned_at']);
            });
        }
    }
};
