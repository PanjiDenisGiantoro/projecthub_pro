<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->enum('error_category', [
                'frontend', 'backend', 'database', 'api', 'infrastructure', 'integration', 'configuration', 'other',
            ])->nullable()->after('type');
            $table->text('solution')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('bug_tickets', function (Blueprint $table) {
            $table->dropColumn(['error_category', 'solution']);
        });
    }
};
