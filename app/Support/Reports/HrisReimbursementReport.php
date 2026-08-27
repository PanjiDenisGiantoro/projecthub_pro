<?php

namespace App\Support\Reports;

use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Support\Collection;

class HrisReimbursementReport implements ReportQuery
{
    private const STATUS_LABELS = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    public function filters(): array
    {
        return [
            'user_id' => [
                'type'    => 'select',
                'label'   => 'Karyawan',
                'options' => User::query()
                    ->when(auth()->user() && !auth()->user()->is_super_admin, fn ($q) => $q->where('company_id', auth()->user()->company_id))
                    ->orderBy('name')->pluck('name', 'id')->all(),
            ],
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => self::STATUS_LABELS,
            ],
            'expense_date' => [
                'type'  => 'date_range',
                'label' => 'Tanggal Pengeluaran',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'karyawan' => 'Karyawan',
            'judul'    => 'Judul',
            'kategori' => 'Kategori',
            'tanggal'  => 'Tanggal',
            'jumlah'   => 'Jumlah',
            'status'   => 'Status',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Reimbursement::query()->with('user')
            ->when(!$user->is_super_admin && $user->company_id, fn ($q) => $q->where('company_id', $user->company_id));

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['expense_date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['expense_date_from']);
        }
        if (!empty($filters['expense_date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['expense_date_to']);
        }

        return $query->orderByDesc('expense_date')->get()->map(fn (Reimbursement $r) => [
            'karyawan' => $r->user?->name ?? '-',
            'judul'    => $r->title,
            'kategori' => $r->category,
            'tanggal'  => $r->expense_date?->format('d/m/Y'),
            'jumlah'   => 'Rp' . number_format((float) $r->amount, 0, ',', '.'),
            'status'   => self::STATUS_LABELS[$r->status] ?? $r->status,
        ]);
    }
}
