<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('forum_message_attachments')) {
            Schema::create('forum_message_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('forum_messages')->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('direct_message_attachments')) {
            Schema::create('direct_message_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('direct_messages')->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_message_attachments');
        Schema::dropIfExists('forum_message_attachments');
    }
};
