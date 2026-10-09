<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // amount tetap = total yang ditagih ke DOKU (subtotal + PPN).
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal')->nullable()->after('package_name');
            $table->decimal('ppn_rate', 5, 2)->default(0)->after('subtotal');
            $table->unsignedBigInteger('ppn_amount')->default(0)->after('ppn_rate');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'ppn_rate', 'ppn_amount']);
        });
    }
};
