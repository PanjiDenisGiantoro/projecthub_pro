<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense'])->default('expense');
            $table->string('category'); // e.g. "Labor", "Software", "Hardware"
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('entry_date');
            $table->string('reference')->nullable(); // invoice/PO number
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget_alert_threshold', 5, 2)->nullable()->after('budget'); // percentage 0-100
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('budget_alert_threshold');
        });
        Schema::dropIfExists('budget_entries');
    }
};
