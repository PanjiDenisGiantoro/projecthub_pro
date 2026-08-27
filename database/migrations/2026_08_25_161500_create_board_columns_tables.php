<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 50);
            $table->string('color', 20)->default('gray');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_done')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });

        Schema::create('board_column_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('board_column_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('board_column_templates')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 50);
            $table->string('color', 20)->default('gray');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_done')->default(false);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('board_column_id')->nullable()->after('status')->constrained('board_columns')->nullOnDelete();
        });

        // Widen the status enum to a plain string so custom per-project column
        // slugs (e.g. "testing", "blocked") can be stored. Raw SQL because
        // doctrine/dbal's MySQL ENUM introspection is unreliable for this
        // specific alter (see board-columns implementation plan).
        DB::statement("ALTER TABLE tasks MODIFY status VARCHAR(50) NOT NULL DEFAULT 'todo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo', 'in_progress', 'review', 'done') NOT NULL DEFAULT 'todo'");

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('board_column_id');
        });

        Schema::dropIfExists('board_column_template_items');
        Schema::dropIfExists('board_column_templates');
        Schema::dropIfExists('board_columns');
    }
};
