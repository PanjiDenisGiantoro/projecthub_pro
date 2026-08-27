<?php

namespace App\Support\Reports;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Collection;

class HrisLeaveReport implements ReportQuery
{
    private const STATUS_LABELS = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    public function filters(): array
    {
        $companyId = auth()->user()?->company_id;

        return [
            'user_id' => [
                'type'    => 'select',
                'label'   => 'Karyawan',
                'options' => User::query()
                    ->when(auth()->user() && !auth()->user()->is_super_admin, fn ($q) => $q->where('company_id', $companyId))
                    ->orderBy('name')->pluck('name', 'id')->all(),
            ],
            'leave_type_id' => [
                'type'    => 'select',
                'label'   => 'Jenis Cuti',
                'options' => LeaveType::query()
                    ->when(auth()->user() && !auth()->user()->is_super_admin, fn ($q) => $q->where('company_id', $companyId))
                    ->orderBy('name')->pluck('name', 'id')->all(),
            ],
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => self::STATUS_LABELS,
            ],
            'start_date' => [
                'type'  => 'date_range',
                'label' => 'Tanggal Mulai',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'karyawan'   => 'Karyawan',
            'jenis'      => 'Jenis Cuti',
            'mulai'      => 'Tanggal Mulai',
            'selesai'    => 'Tanggal Selesai',
            'total_hari' => 'Total Hari',
            'status'     => 'Status',
            'disetujui'  => 'Disetujui Oleh',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = LeaveRequest::query()->with(['user', 'leaveType', 'approver'])
            ->when(!$user->is_super_admin && $user->company_id, fn ($q) => $q->where('company_id', $user->company_id));

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }
        if (!empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }

        return $query->orderByDesc('start_date')->get()->map(fn (LeaveRequest $l) => [
            'karyawan'   => $l->user?->name ?? '-',
            'jenis'      => $l->leaveType?->name ?? '-',
            'mulai'      => $l->start_date?->format('d/m/Y'),
            'selesai'    => $l->end_date?->format('d/m/Y'),
            'total_hari' => $l->total_days,
            'status'     => self::STATUS_LABELS[$l->status] ?? $l->status,
            'disetujui'  => $l->approver?->name ?? '-',
        ]);
    }
}
