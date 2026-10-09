<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // amount = subtotal + ppn_amount + fee_amount (total yang ditagih ke DOKU).
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->string('payment_method_code')->nullable()->after('ppn_amount');
            $table->string('payment_method_name')->nullable()->after('payment_method_code');
            $table->unsignedBigInteger('fee_amount')->default(0)->after('payment_method_name');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method_code', 'payment_method_name', 'fee_amount']);
        });
    }
};
