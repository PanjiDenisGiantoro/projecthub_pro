<?php

namespace App\Support\Reports;

use App\Models\Kasbon;
use App\Models\User;
use Illuminate\Support\Collection;

class HrisKasbonReport implements ReportQuery
{
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
                'options' => Kasbon::statusLabels(),
            ],
            'tanggal' => [
                'type'  => 'date_range',
                'label' => 'Tanggal Pengajuan',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'karyawan'  => 'Karyawan',
            'tanggal'   => 'Tanggal',
            'jumlah'    => 'Jumlah Pinjaman',
            'cicilan'   => 'Cicilan / Bulan',
            'sisa'      => 'Sisa',
            'status'    => 'Status',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Kasbon::query()->with('user')
            ->when(!$user->is_super_admin && $user->company_id, fn ($q) => $q->where('company_id', $user->company_id));

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['tanggal_from'])) {
            $query->whereDate('tanggal', '>=', $filters['tanggal_from']);
        }
        if (!empty($filters['tanggal_to'])) {
            $query->whereDate('tanggal', '<=', $filters['tanggal_to']);
        }

        $statusLabels = Kasbon::statusLabels();

        return $query->orderByDesc('tanggal')->get()->map(fn (Kasbon $k) => [
            'karyawan' => $k->user?->name ?? '-',
            'tanggal'  => $k->tanggal?->format('d/m/Y'),
            'jumlah'   => 'Rp' . number_format((float) $k->jumlah, 0, ',', '.'),
            'cicilan'  => 'Rp' . number_format((float) $k->cicilan_per_bulan, 0, ',', '.'),
            'sisa'     => 'Rp' . number_format((float) $k->sisa, 0, ',', '.'),
            'status'   => $statusLabels[$k->status] ?? $k->status,
        ]);
    }
}
