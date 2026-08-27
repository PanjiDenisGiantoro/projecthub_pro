<?php

namespace App\Support\Reports;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectsReport implements ReportQuery
{
    public function filters(): array
    {
        return [
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => [
                    'draft'     => 'Draft',
                    'active'    => 'Aktif',
                    'on_hold'   => 'On Hold',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                ],
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
            'id'         => 'ID',
            'name'       => 'Nama Proyek',
            'client'     => 'Klien',
            'manager'    => 'Lead Project',
            'status'     => 'Status',
            'progress'   => 'Progress',
            'budget'     => 'Anggaran',
            'start_date' => 'Mulai',
            'end_date'   => 'Selesai',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Project::query()->with(['client', 'manager']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }
        if (! empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }

        return $query->orderByDesc('id')->get()->map(fn (Project $p) => [
            'id'         => $p->id,
            'name'       => $p->name,
            'client'     => $p->client?->name ?? '-',
            'manager'    => $p->manager?->name ?? '-',
            'status'     => $p->status,
            'progress'   => $p->progress . '%',
            'budget'     => $p->budget !== null ? number_format((float) $p->budget, 0, ',', '.') : '-',
            'start_date' => $p->start_date?->format('d/m/Y') ?? '-',
            'end_date'   => $p->end_date?->format('d/m/Y') ?? '-',
        ]);
    }
}
