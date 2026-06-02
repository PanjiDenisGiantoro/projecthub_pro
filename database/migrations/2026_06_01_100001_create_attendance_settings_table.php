<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Location validation
            $table->boolean('is_location_enabled')->default(false);
            $table->string('office_name')->nullable();
            $table->decimal('office_latitude', 10, 8)->nullable();
            $table->decimal('office_longitude', 11, 8)->nullable();
            $table->unsignedInteger('max_distance_meters')->default(100);
            $table->boolean('require_location_for_checkout')->default(false);

            // Face recognition
            $table->boolean('is_face_recognition_enabled')->default(false);
            $table->float('face_recognition_threshold')->default(0.55); // 0=strict, 1=loose
            $table->boolean('require_face_for_checkout')->default(false);

            $table->timestamps();
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
