<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['technical', 'schedule', 'resource', 'budget', 'external', 'other'])->default('other');
            $table->unsignedTinyInteger('probability')->default(1); // 1-5
            $table->unsignedTinyInteger('impact')->default(1);      // 1-5
            $table->enum('status', ['open', 'mitigated', 'accepted', 'closed'])->default('open');
            $table->text('mitigation_plan')->nullable();
            $table->string('owner')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
