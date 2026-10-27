<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionOrder extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'company_id',
        'package_id',
        'package_name',
        'billing_cycle',
        'user_count',
        'promo_id',
        'promo_name',
        'discount_amount',
        'subtotal',
        'ppn_rate',
        'ppn_amount',
        'payment_method_code',
        'payment_method_name',
        'fee_amount',
        'amount',
        'duration_days',
        'status',
        'midtrans_transaction_id',
        'snap_token',
        'paid_at',
        'raw_notification',
    ];

    protected $casts = [
        'subtotal'         => 'integer',
        'user_count'       => 'integer',
        'discount_amount'  => 'integer',
        'ppn_rate'         => 'float',
        'ppn_amount'       => 'integer',
        'fee_amount'       => 'integer',
        'amount'           => 'integer',
        'duration_days'    => 'integer',
        'paid_at'          => 'datetime',
        'raw_notification' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }

    /** Harga paket sebelum promo (subtotal sudah dipotong promo). */
    public function grossAmount(): int
    {
        return (int) ($this->subtotal ?? $this->amount) + (int) $this->discount_amount;
    }

    /** "12 user · Tahunan" */
    public function cycleLabel(): string
    {
        $cycle = $this->billing_cycle === 'yearly' ? 'Tahunan' : 'Bulanan';

        return $this->user_count ? "{$this->user_count} user · {$cycle}" : $cycle;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'expired']);
    }
}
