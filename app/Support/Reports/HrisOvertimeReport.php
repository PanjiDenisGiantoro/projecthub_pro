<?php

namespace App\Support\Reports;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Support\Collection;

class HrisOvertimeReport implements ReportQuery
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
            'date' => [
                'type'  => 'date_range',
                'label' => 'Tanggal',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'karyawan'     => 'Karyawan',
            'tanggal'      => 'Tanggal',
            'jam'          => 'Jam',
            'total_jam'    => 'Total Jam',
            'total_upah'   => 'Total Upah',
            'status'       => 'Status',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Overtime::query()->with('user')
            ->when(!$user->is_super_admin && $user->company_id, fn ($q) => $q->where('company_id', $user->company_id));

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('date')->get()->map(fn (Overtime $o) => [
            'karyawan'   => $o->user?->name ?? '-',
            'tanggal'    => $o->date?->format('d/m/Y'),
            'jam'        => ($o->start_time ?? '-') . ' - ' . ($o->end_time ?? '-'),
            'total_jam'  => $o->total_hours,
            'total_upah' => 'Rp' . number_format((float) $o->total_amount, 0, ',', '.'),
            'status'     => self::STATUS_LABELS[$o->status] ?? $o->status,
        ]);
    }
}
