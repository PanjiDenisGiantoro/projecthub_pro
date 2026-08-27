<?php

namespace App\Support\Reports;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Collection;

class HrisPayrollReport implements ReportQuery
{
    private const STATUS_LABELS = [
        'draft'     => 'Draft',
        'finalized' => 'Final',
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
            'year' => [
                'type'    => 'select',
                'label'   => 'Tahun',
                'options' => collect(range(now()->year, now()->year - 4))->mapWithKeys(fn ($y) => [$y => $y])->all(),
            ],
            'month' => [
                'type'    => 'select',
                'label'   => 'Bulan',
                'options' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m)->locale('id')->isoFormat('MMMM')])->all(),
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'karyawan'    => 'Karyawan',
            'periode'     => 'Periode',
            'gaji_pokok'  => 'Gaji Pokok',
            'penghasilan_bruto' => 'Penghasilan Bruto',
            'total_potongan'    => 'Total Potongan',
            'gaji_bersih' => 'Gaji Bersih (Take Home Pay)',
            'status'      => 'Status',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Payroll::query()->with('user')
            ->when(!$user->is_super_admin && $user->company_id, fn ($q) => $q->where('company_id', $user->company_id));

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }
        if (!empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        return $query->orderByDesc('year')->orderByDesc('month')->get()->map(fn (Payroll $p) => [
            'karyawan'           => $p->user?->name ?? '-',
            'periode'            => \Carbon\Carbon::create($p->year, $p->month)->locale('id')->isoFormat('MMMM Y'),
            'gaji_pokok'         => 'Rp' . number_format((float) $p->gaji_pokok, 0, ',', '.'),
            'penghasilan_bruto'  => 'Rp' . number_format((float) $p->penghasilan_bruto, 0, ',', '.'),
            'total_potongan'     => 'Rp' . number_format((float) $p->total_potongan, 0, ',', '.'),
            'gaji_bersih'        => 'Rp' . number_format((float) $p->gaji_bersih, 0, ',', '.'),
            'status'             => self::STATUS_LABELS[$p->status] ?? $p->status,
        ]);
    }
}
