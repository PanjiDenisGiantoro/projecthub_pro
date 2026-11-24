<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Support\Str;

/** Pembuatan order langganan + transaksi DOKU Checkout, dipakai web (billing.checkout) & API (/api/billing/checkout). */
class SubscriptionCheckoutService
{
    public function __construct(protected DokuService $doku)
    {
    }

    /**
     * Buat SubscriptionOrder pending & link pembayaran DOKU.
     * Biaya layanan ditanggung customer, dihitung dari harga + PPN; DOKU dikunci ke metode terpilih.
     *
     * @throws \Throwable bila DOKU gagal (order tetap tersimpan sebagai pending tanpa payment_url).
     */
    public function checkout(User $registrant, Package $package, int $users, string $cycle, PaymentMethod $method): SubscriptionOrder
    {
        $quote = $package->quote($users, $cycle, $registrant->company_id);
        $fee   = $method->feeFor($quote['total']);

        $order = SubscriptionOrder::create([
            'order_number'          => 'SUB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'user_id'               => $registrant->id,
            'company_id'            => $registrant->company_id,
            'package_id'            => $package->id,
            'package_name'          => $package->name,
            'billing_cycle'         => $quote['cycle'],
            'user_count'            => $quote['users'],
            'promo_id'              => $quote['promo']?->id,
            'promo_name'            => $quote['promo']?->name,
            'discount_amount'       => $quote['discount'],
            'subtotal'              => $quote['subtotal'],
            'ppn_rate'              => $quote['rate'],
            'ppn_amount'            => $quote['ppn'],
            'payment_method_code'   => $method->code,
            'payment_method_name'   => $method->name,
            'fee_amount'            => $fee,
            'amount'                => $quote['total'] + $fee,
            'duration_days'         => $quote['duration_days'],
            'status'                => 'pending',
        ]);

        $result = $this->doku->createPayment($order);

        $order->update([
            'snap_token'         => $result['token_id'] ?? null,
            'payment_url'        => $result['url'],
            'payment_expires_at' => now()->addMinutes((int) config('services.doku.payment_due_minutes', 60)),
        ]);

        return $order;
    }
}
