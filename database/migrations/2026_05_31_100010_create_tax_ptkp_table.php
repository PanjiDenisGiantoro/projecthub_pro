<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_ptkp', function (Blueprint $table) {
            $table->id();
            $table->string('status_code', 10)->unique();
            $table->string('label');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_ptkp');
    }
};
