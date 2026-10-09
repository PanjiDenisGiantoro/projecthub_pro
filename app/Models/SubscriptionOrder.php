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

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'expired']);
    }
}
