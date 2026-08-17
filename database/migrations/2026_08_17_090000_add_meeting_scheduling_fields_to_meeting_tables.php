<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dateTime('meeting_starts_at')->nullable()->after('google_meet_link');
        });

        Schema::table('sprints', function (Blueprint $table) {
            $table->dateTime('meeting_starts_at')->nullable()->after('google_meet_link');
            $table->foreignId('google_meeting_organizer_id')->nullable()->after('meeting_starts_at')->constrained('users')->nullOnDelete();
            $table->boolean('google_meeting_is_recurring')->default(false)->after('google_meeting_organizer_id');
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dateTime('meeting_starts_at')->nullable()->after('google_meet_link');
            $table->foreignId('google_meeting_organizer_id')->nullable()->after('meeting_starts_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dateTime('meeting_starts_at')->nullable()->after('google_meet_link');
            $table->foreignId('google_meeting_organizer_id')->nullable()->after('meeting_starts_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->dateTime('meeting_starts_at')->nullable()->after('google_meet_link');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['meeting_starts_at']);
        });

        Schema::table('sprints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('google_meeting_organizer_id');
            $table->dropColumn(['meeting_starts_at', 'google_meeting_is_recurring']);
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('google_meeting_organizer_id');
            $table->dropColumn(['meeting_starts_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('google_meeting_organizer_id');
            $table->dropColumn(['meeting_starts_at']);
        });

        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->dropColumn(['meeting_starts_at']);
        });
    }
};
