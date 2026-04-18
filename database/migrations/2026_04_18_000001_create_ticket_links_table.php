<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_ticket_id')->constrained('bug_tickets')->cascadeOnDelete();
            $table->foreignId('target_ticket_id')->constrained('bug_tickets')->cascadeOnDelete();
            $table->enum('link_type', ['blocks', 'blocked_by', 'duplicates', 'duplicated_by', 'relates_to', 'caused_by', 'causes'])->default('relates_to');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['source_ticket_id', 'target_ticket_id', 'link_type']);
        });

        Schema::table('ticket_histories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('new_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_links');
        Schema::table('ticket_histories', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
