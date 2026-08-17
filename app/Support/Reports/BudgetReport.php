<?php

namespace App\Support\Reports;

use App\Models\BudgetEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class BudgetReport implements ReportQuery
{
    public function filters(): array
    {
        return [
            'project_id' => [
                'type'    => 'select',
                'label'   => 'Proyek',
                'options' => Project::query()->orderBy('name')->pluck('name', 'id')->all(),
            ],
            'type' => [
                'type'    => 'select',
                'label'   => 'Tipe',
                'options' => [
                    'income'  => 'Pemasukan',
                    'expense' => 'Pengeluaran',
                ],
            ],
            'entry_date' => [
                'type'  => 'date_range',
                'label' => 'Tanggal',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'id'          => 'ID',
            'project'     => 'Proyek',
            'type'        => 'Tipe',
            'category'    => 'Kategori',
            'description' => 'Deskripsi',
            'amount'      => 'Jumlah',
            'entry_date'  => 'Tanggal',
            'creator'     => 'Oleh',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = BudgetEntry::query()->with(['project', 'creator']);

        $query->whereHas('project', function ($q) use ($user) {
            if (! $user->is_super_admin && $user->company_id) {
                $q->where('company_id', $user->company_id);
            }
        });

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['entry_date_from'])) {
            $query->whereDate('entry_date', '>=', $filters['entry_date_from']);
        }
        if (! empty($filters['entry_date_to'])) {
            $query->whereDate('entry_date', '<=', $filters['entry_date_to']);
        }

        return $query->orderByDesc('entry_date')->get()->map(fn (BudgetEntry $e) => [
            'id'          => $e->id,
            'project'     => $e->project?->name,
            'type'        => $e->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            'category'    => $e->category,
            'description' => $e->description,
            'amount'      => number_format((float) $e->amount, 0, ',', '.'),
            'entry_date'  => $e->entry_date?->format('d/m/Y'),
            'creator'     => $e->creator?->name ?? '-',
        ]);
    }
}
