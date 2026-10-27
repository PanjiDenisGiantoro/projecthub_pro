<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promo harga langganan: berlaku dalam periode start_date–end_date, potongan persen atau nominal
 * dari subtotal (sebelum PPN), untuk semua perusahaan (company_id null) atau satu perusahaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // null = semua perusahaan
            $table->string('discount_type', 10); // percent | nominal
            $table->decimal('discount_value', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = tanpa batas
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->foreignId('promo_id')->nullable()->after('user_count')->constrained()->nullOnDelete();
            $table->string('promo_name')->nullable()->after('promo_id');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_id');
            $table->dropColumn(['promo_name', 'discount_amount']);
        });

        Schema::dropIfExists('promos');
    }
};
