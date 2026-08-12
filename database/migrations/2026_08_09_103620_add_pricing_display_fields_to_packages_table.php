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
        Schema::table('packages', function (Blueprint $table) {
            $table->string('type', 20)->default('tier')->after('slug'); // tier = kartu harga, module = add-on
            $table->string('tagline')->nullable()->after('description');
            $table->string('price_display')->nullable()->after('price'); // override teks harga, mis. "Custom"
            $table->string('price_period')->nullable()->after('price_display'); // "per bulan / tim", "Selamanya gratis"
            $table->boolean('is_popular')->default(false)->after('is_active');
            $table->string('cta_label')->nullable()->after('is_popular');
            $table->string('cta_type', 20)->default('register')->after('cta_label'); // register | contact
            $table->unsignedInteger('sort_order')->default(0)->after('cta_type');

            $table->unsignedBigInteger('price')->nullable()->default(null)->change();
            $table->unsignedInteger('duration_days')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['type', 'tagline', 'price_display', 'price_period', 'is_popular', 'cta_label', 'cta_type', 'sort_order']);
            $table->unsignedBigInteger('price')->default(0)->change();
            $table->unsignedInteger('duration_days')->default(30)->change();
        });
    }
};
