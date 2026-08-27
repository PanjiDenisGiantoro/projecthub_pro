<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // string, bukan enum DB — daftar tipe divalidasi di app (App\Support\EmploymentType)
            // supaya menambah tipe baru nanti tidak perlu migration ubah enum.
            $table->string('employment_type')->default('tetap')->after('structural_level_id');
            $table->string('outsourcing_company_name')->nullable()->after('employment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employment_type', 'outsourcing_company_name']);
        });
    }
};
