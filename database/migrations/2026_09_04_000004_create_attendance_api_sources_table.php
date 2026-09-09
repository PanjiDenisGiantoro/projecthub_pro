<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_api_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('method', 4)->default('get'); // get | post
            $table->text('url');
            $table->string('auth_type', 20)->default('none'); // none | api_key | bearer | basic
            $table->text('auth_config')->nullable(); // encrypted json, bentuk tergantung auth_type
            $table->text('request_template')->nullable(); // json — query params (GET) atau body (POST), placeholder {{date}} dst
            $table->string('response_data_path', 150)->nullable(); // dot path ke array baris di body response, kosong = root
            $table->text('response_mapping')->nullable(); // json — mapping field response -> kolom attendance + match_field
            $table->boolean('auto_insert')->default(false);
            $table->boolean('overwrite_on_conflict')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_api_sources');
    }
};
