<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folder kosong (belum ada file) di File Manager project. Folder yang sudah
     * ada isinya cukup diketahui lewat kolom project_files.folder — tabel ini
     * cuma untuk folder yang dibuat duluan sebelum file diunggah ke dalamnya.
     */
    public function up(): void
    {
        Schema::create('project_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_folders');
    }
};
