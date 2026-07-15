<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->integer('default_quota')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('needs_attachment')->default(false);
            $table->boolean('needs_approval')->default(true);
            $table->boolean('has_balance')->default(true);
            $table->enum('gender_restriction', ['all', 'male', 'female'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
