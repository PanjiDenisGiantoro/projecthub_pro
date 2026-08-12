<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_ter_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['A', 'B', 'C']); // PMK 168/2023 — kategori TER berdasarkan status PTKP
            $table->decimal('income_from', 15, 2);
            $table->decimal('income_to', 15, 2)->nullable(); // null = tak terbatas (lapisan tertinggi)
            $table->decimal('rate', 6, 4); // desimal, mis. 0.0025 = 0.25%
            $table->string('label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_ter_rates');
    }
};
