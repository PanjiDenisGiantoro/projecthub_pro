<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('goal')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['planned', 'active', 'completed'])->default('planned');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('sprint_id')->nullable()->after('milestone_id')->constrained('sprints')->nullOnDelete();
            $table->unsignedSmallInteger('story_points')->nullable()->after('estimated_hours');
            $table->unsignedInteger('sort_order')->default(0)->after('story_points');
            $table->foreignId('recurring_definition_id')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sprint_id');
            $table->dropColumn(['story_points', 'sort_order', 'recurring_definition_id']);
        });
        Schema::dropIfExists('sprints');
    }
};
