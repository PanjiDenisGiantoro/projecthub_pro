<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pph21_settings', function (Blueprint $table) {
            $table->boolean('overtime_needs_approval')->default(true)->after('tax_tunjangan_makan');
            $table->boolean('reimbursement_needs_approval')->default(true)->after('overtime_needs_approval');
        });
    }

    public function down(): void
    {
        Schema::table('pph21_settings', function (Blueprint $table) {
            $table->dropColumn(['overtime_needs_approval', 'reimbursement_needs_approval']);
        });
    }
};
