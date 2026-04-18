<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('milestones', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'assigned_to');
            $table->dropColumn('assigned_to');
        });
    }
};
