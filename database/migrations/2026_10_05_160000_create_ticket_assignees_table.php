<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ticket_assignees')) {
            Schema::create('ticket_assignees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('bug_tickets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['ticket_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_assignees');
    }
};
