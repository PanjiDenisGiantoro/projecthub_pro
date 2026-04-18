<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // deadline_reminder, task_assigned, etc.
            $table->morphs('notifiable'); // polymorphic to Task, Project, etc.
            $table->string('email');
            $table->string('subject');
            $table->enum('status', ['sent', 'failed', 'skipped'])->default('sent');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['type', 'notifiable_type', 'notifiable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_notification_logs');
    }
};
