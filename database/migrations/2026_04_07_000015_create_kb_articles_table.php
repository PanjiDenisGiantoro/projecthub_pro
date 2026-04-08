<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->string('title');
            $table->longText('body');
            $table->foreignId('parent_id')->nullable()->constrained('kb_articles')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->fullText(['title', 'body']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
    }
};
