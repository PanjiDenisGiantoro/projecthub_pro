<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link DOKU Checkout disimpan supaya order pending bisa dibayar lagi dari riwayat sampai batas waktunya.
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->string('payment_url', 512)->nullable()->after('snap_token');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_url');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_url', 'payment_expires_at']);
        });
    }
};
