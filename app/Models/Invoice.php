<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'project_id', 'client_id', 'invoice_number', 'status',
        'issue_date', 'due_date', 'subtotal', 'tax', 'total', 'paid_at', 'notes',
    ];

    protected static function booted(): void
    {
        // Auto-filter per tenant; super admin bypass (sama seperti Project::booted()).
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && ! auth()->user()->is_super_admin && $cid = auth()->user()->company_id) {
                $builder->where('invoices.company_id', $cid);
            }
        });

        static::creating(function (Invoice $invoice) {
            if (! $invoice->company_id && auth()->check() && auth()->user()->company_id) {
                $invoice->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum('total');
        $this->subtotal = $subtotal;
        $this->total = $subtotal + ($subtotal * $this->tax / 100);
        $this->save();
    }

    public static function generateNumber(): string
    {
        $last = static::withTrashed()->latest()->first();
        $seq = $last ? ((int) substr($last->invoice_number, -4) + 1) : 1;
        return 'INV-' . now()->format('Ym') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
