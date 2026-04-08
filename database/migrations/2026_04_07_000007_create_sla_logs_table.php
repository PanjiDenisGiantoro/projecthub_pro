<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('bug_tickets')->cascadeOnDelete();
            $table->enum('event_type', ['paused', 'resumed', 'breached', 'escalated', 'warning']);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_logs');
    }
};
