<?php

namespace App\Support\Reports;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Collection;

class HrisAttendanceReport implements ReportQuery
{
    private const STATUS_LABELS = [
        'hadir' => 'Hadir',
        'izin'  => 'Izin',
        'sakit' => 'Sakit',
        'cuti'  => 'Cuti',
        'alpha' => 'Alpha',
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
            'karyawan'   => 'Karyawan',
            'tanggal'    => 'Tanggal',
            'jam_masuk'  => 'Jam Masuk',
            'jam_keluar' => 'Jam Keluar',
            'status'     => 'Status',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Attendance::query()->with('user')
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

        return $query->orderByDesc('date')->get()->map(fn (Attendance $a) => [
            'karyawan'   => $a->user?->name ?? '-',
            'tanggal'    => $a->date?->format('d/m/Y'),
            'jam_masuk'  => $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '-',
            'jam_keluar' => $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '-',
            'status'     => self::STATUS_LABELS[$a->status] ?? $a->status,
        ]);
    }
}
