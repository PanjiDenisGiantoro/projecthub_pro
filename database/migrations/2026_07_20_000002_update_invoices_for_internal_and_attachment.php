<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * project_id dibuat nullable supaya invoice bisa dibuat tanpa proyek
     * (invoice internal). doctrine/dbal tidak terpasang, jadi ubah kolom
     * lewat raw SQL, bukan Schema::table(...)->change().
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        DB::statement('ALTER TABLE invoices MODIFY project_id BIGINT UNSIGNED NULL');

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->string('attachment')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('attachment');
            $table->dropForeign(['project_id']);
        });

        DB::statement('ALTER TABLE invoices MODIFY project_id BIGINT UNSIGNED NOT NULL');

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }
};
