<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_working_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            // 0 = Minggu ... 6 = Sabtu, ikut konvensi Carbon::dayOfWeek().
            $table->unsignedTinyInteger('day_of_week');
            $table->timestamps();

            $table->unique(['shift_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_working_days');
    }
};
