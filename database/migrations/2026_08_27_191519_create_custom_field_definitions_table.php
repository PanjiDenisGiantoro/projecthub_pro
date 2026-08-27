<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // key dipakai sebagai JSON key di users.custom_fields sekaligus nama input
            // form (custom_fields[key]) — makanya slug, bukan label bebas.
            $table->string('key', 100);
            $table->string('label');
            $table->string('type', 20)->default('text'); // text|number|date|select|checkbox|textarea
            $table->json('options')->nullable(); // daftar pilihan buat type=select
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_definitions');
    }
};
