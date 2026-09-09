<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_api_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_api_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 10); // preview | apply
            $table->string('status', 10); // success | failed | partial
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->unsignedInteger('rows_fetched')->default(0);
            $table->unsignedInteger('rows_created')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_api_sync_logs');
    }
};
