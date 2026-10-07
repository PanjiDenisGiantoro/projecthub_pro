<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Backfill existing projects with slugs
        foreach (Project::withoutGlobalScopes()->get() as $project) {
            $baseSlug = Str::slug($project->name) ?: ('project-' . $project->id);
            $slug = $baseSlug;
            $counter = 1;
            while (Project::withoutGlobalScopes()->where('slug', $slug)->where('id', '!=', $project->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $project->updateQuietly(['slug' => $slug]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
