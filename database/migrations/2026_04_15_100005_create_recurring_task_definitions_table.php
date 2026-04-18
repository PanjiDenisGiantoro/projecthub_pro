<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_task_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly']);
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=Sun..6=Sat for weekly
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-28 for monthly
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->unsignedSmallInteger('estimated_hours')->nullable();
            $table->unsignedSmallInteger('due_offset_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->date('last_generated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_task_definitions');
    }
};
