<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_template_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('project_templates')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('offset_days')->default(0);
            $table->unsignedSmallInteger('duration_days')->default(7);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('project_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_milestone_id')->constrained('project_template_milestones')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->unsignedSmallInteger('estimated_hours')->nullable();
            $table->unsignedSmallInteger('story_points')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_template_tasks');
        Schema::dropIfExists('project_template_milestones');
        Schema::dropIfExists('project_templates');
    }
};
