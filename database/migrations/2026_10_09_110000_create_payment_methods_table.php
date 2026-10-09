<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master metode bayar DOKU Checkout + biaya layanan yang dibebankan ke customer.
        // code = nilai payment_method_types DOKU (mis. VIRTUAL_ACCOUNT_BCA, QRIS).
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('group'); // Virtual Account | QRIS | E-Wallet | Kartu Kredit
            $table->unsignedInteger('fee_flat')->default(0);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Fee awal = perkiraan umum MDR DOKU, sesuaikan dengan kontrak di Super Admin → Metode Bayar.
        // QRIS 0: aturan BI melarang MDR QRIS dibebankan ke customer.
        $rows = [
            ['VIRTUAL_ACCOUNT_BCA',          'BCA Virtual Account',     'Virtual Account', 4500, 0],
            ['VIRTUAL_ACCOUNT_BANK_MANDIRI', 'Mandiri Virtual Account', 'Virtual Account', 4500, 0],
            ['VIRTUAL_ACCOUNT_BRI',          'BRI Virtual Account',     'Virtual Account', 4500, 0],
            ['VIRTUAL_ACCOUNT_BNI',          'BNI Virtual Account',     'Virtual Account', 4500, 0],
            ['QRIS',                         'QRIS',                    'QRIS',            0,    0],
            ['EMONEY_OVO',                   'OVO',                     'E-Wallet',        0,    2],
            ['EMONEY_DANA',                  'DANA',                    'E-Wallet',        0,    1.5],
            ['EMONEY_SHOPEE_PAY',            'ShopeePay',               'E-Wallet',        0,    2],
            ['CREDIT_CARD',                  'Kartu Kredit',            'Kartu Kredit',    2000, 2.9],
        ];

        foreach ($rows as $i => [$code, $name, $group, $flat, $pct]) {
            DB::table('payment_methods')->insert([
                'code' => $code, 'name' => $name, 'group' => $group,
                'fee_flat' => $flat, 'fee_percent' => $pct,
                'is_active' => true, 'sort_order' => $i,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
