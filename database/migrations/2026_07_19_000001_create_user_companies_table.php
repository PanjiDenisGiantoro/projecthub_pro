<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akses tambahan seorang user ke company lain di luar company utamanya
     * (users.company_id). Dipakai supaya user non-superadmin bisa mengelola
     * Organization Units milik beberapa company sekaligus.
     */
    public function up(): void
    {
        Schema::create('user_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_companies');
    }
};
