<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove legacy JSON column if it still exists
        if (Schema::hasColumn('users', 'packages')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('packages');
            });
        }

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('package_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_user');
        Schema::dropIfExists('packages');

        // Restore legacy JSON column
        if (! Schema::hasColumn('users', 'packages')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('packages')->nullable()->after('is_registered');
            });
        }
    }
};
