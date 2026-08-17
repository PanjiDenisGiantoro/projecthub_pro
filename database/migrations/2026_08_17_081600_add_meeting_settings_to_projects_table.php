<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('google_meet_enabled')->default(true)->after('progress');
            $table->boolean('meeting_auto_create')->default(false)->after('google_meet_enabled');
            $table->time('meeting_default_time')->nullable()->after('meeting_auto_create');
            $table->unsignedInteger('meeting_default_duration_minutes')->default(60)->after('meeting_default_time');
            $table->string('google_event_id')->nullable()->after('meeting_default_duration_minutes');
            $table->string('google_meet_link')->nullable()->after('google_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'google_meet_enabled',
                'meeting_auto_create',
                'meeting_default_time',
                'meeting_default_duration_minutes',
                'google_event_id',
                'google_meet_link',
            ]);
        });
    }
};
