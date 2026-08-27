<?php

namespace App\Support\Reports;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectTasksReport implements ReportQuery
{
    public function filters(): array
    {
        return [
            'project_id' => [
                'type'    => 'select',
                'label'   => 'Proyek',
                'options' => Project::query()->orderBy('name')->pluck('name', 'id')->all(),
            ],
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => [
                    'todo'        => 'To Do',
                    'in_progress' => 'In Progress',
                    'review'      => 'Review',
                    'done'        => 'Done',
                ],
            ],
            'due_date' => [
                'type'  => 'date_range',
                'label' => 'Tenggat Waktu',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'id'         => 'ID',
            'title'      => 'Judul',
            'project'    => 'Proyek',
            'assignee'   => 'Assignee',
            'status'     => 'Status',
            'priority'   => 'Prioritas',
            'start_date' => 'Mulai',
            'due_date'   => 'Tenggat',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Task::query()->whereNull('deleted_at')->with(['project', 'assignee']);

        $query->whereHas('project', function ($q) use ($user) {
            if (! $user->is_super_admin && $user->company_id) {
                $q->where('company_id', $user->company_id);
            }
        });

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }
        if (! empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        return $query->orderByDesc('id')->get()->map(fn (Task $t) => [
            'id'         => $t->id,
            'title'      => $t->title,
            'project'    => $t->project?->name,
            'assignee'   => $t->assignee?->name ?? '-',
            'status'     => $t->status,
            'priority'   => $t->priority,
            'start_date' => $t->start_date?->format('d/m/Y') ?? '-',
            'due_date'   => $t->due_date?->format('d/m/Y') ?? '-',
        ]);
    }
}
