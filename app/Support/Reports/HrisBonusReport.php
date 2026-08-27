<?php

namespace App\Support\Reports;

use App\Models\Bonus;
use App\Models\User;
use Illuminate\Support\Collection;

class HrisBonusReport implements ReportQuery
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
            'type' => [
                'type'    => 'select',
                'label'   => 'Tipe',
                'options' => Bonus::typeLabels(),
            ],
            'year' => [
                'type'    => 'select',
                'label'   => 'Tahun',
                'options' => collect(range(now()->year, now()->year - 4))->mapWithKeys(fn ($y) => [$y => $y])->all(),
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'karyawan' => 'Karyawan',
            'tipe'     => 'Tipe',
            'periode'  => 'Periode',
            'jumlah'   => 'Jumlah',
            'catatan'  => 'Catatan',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Bonus::query()->with('user')
            ->when(!$user->is_super_admin && $user->company_id, fn ($q) => $q->where('company_id', $user->company_id));

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        $typeLabels = Bonus::typeLabels();

        return $query->orderByDesc('year')->orderByDesc('month')->get()->map(fn (Bonus $b) => [
            'karyawan' => $b->user?->name ?? '-',
            'tipe'     => $typeLabels[$b->type] ?? $b->type,
            'periode'  => $b->month ? \Carbon\Carbon::create($b->year, $b->month)->locale('id')->isoFormat('MMMM Y') : $b->year,
            'jumlah'   => 'Rp' . number_format((float) $b->amount, 0, ',', '.'),
            'catatan'  => $b->description ?? '-',
        ]);
    }
}
