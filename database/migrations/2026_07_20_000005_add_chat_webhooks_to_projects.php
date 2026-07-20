<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('slack_webhook_url')->nullable()->after('github_token');
            $table->text('discord_webhook_url')->nullable()->after('slack_webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['slack_webhook_url', 'discord_webhook_url']);
        });
    }
};