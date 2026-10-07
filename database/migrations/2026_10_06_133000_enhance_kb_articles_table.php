<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->string('category', 50)->default('other')->after('title');
            $table->text('external_url')->nullable()->after('description');
            $table->boolean('is_pinned')->default(false)->after('version');
            $table->longText('body')->nullable()->change();
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'external_url', 'is_pinned']);
            $table->longText('body')->nullable(false)->change();
        });
    }
};
