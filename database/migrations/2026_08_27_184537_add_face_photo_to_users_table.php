<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Path (disk 'public') ke foto wajah yang diambil saat pendaftaran —
            // dipakai buat preview visual, terpisah dari face_descriptor (128 float
            // buat matching face-api.js) yang tidak bisa direpresentasikan sebagai gambar.
            $table->string('face_photo')->nullable()->after('face_descriptor');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('face_photo');
        });
    }
};
