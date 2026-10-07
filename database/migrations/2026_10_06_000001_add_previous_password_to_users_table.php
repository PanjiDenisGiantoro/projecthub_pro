<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menyimpan hash password sebelumnya saat superadmin mengganti password,
            // supaya bisa dikembalikan (swap) lewat halaman superadmin.
            $table->string('previous_password')->nullable()->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('previous_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['previous_password', 'password_changed_at']);
        });
    }
};
