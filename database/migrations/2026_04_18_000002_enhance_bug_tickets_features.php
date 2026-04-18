<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->foreignId('merged_into_id')->nullable()->after('id')
                ->constrained('bug_tickets')->nullOnDelete();
            $table->boolean('sla_paused')->default(false)->after('sla_breached');
            $table->timestamp('sla_paused_at')->nullable()->after('sla_paused');
        });

        Schema::create('ticket_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('bug_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['ticket_id', 'user_id']);
            $table->timestamps();
        });

        Schema::create('ticket_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('bug_tickets')->cascadeOnDelete();
            $table->string('body');
            $table->boolean('is_done')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('ticket_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['bug', 'issue', 'enhancement', 'security', 'performance'])->default('bug');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->text('description_template');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('sla_pauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('bug_tickets')->cascadeOnDelete();
            $table->foreignId('paused_by')->constrained('users');
            $table->text('reason');
            $table->timestamp('paused_at');
            $table->timestamp('resumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_pauses');
        Schema::dropIfExists('ticket_templates');
        Schema::dropIfExists('ticket_checklists');
        Schema::dropIfExists('ticket_watchers');
        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->dropForeign(['merged_into_id']);
            $table->dropColumn(['merged_into_id', 'sla_paused', 'sla_paused_at']);
        });
    }
};
