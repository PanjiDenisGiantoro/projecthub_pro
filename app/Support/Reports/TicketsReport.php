<?php

namespace App\Support\Reports;

use App\Models\BugTicket;
use App\Models\User;
use Illuminate\Support\Collection;

class TicketsReport implements ReportQuery
{
    public function filters(): array
    {
        return [
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => [
                    'open'           => 'Open',
                    'assigned'       => 'Assigned',
                    'in_progress'    => 'In Progress',
                    'pending_review' => 'Pending Review',
                    'resolved'       => 'Resolved',
                    'closed'         => 'Closed',
                    'reopened'       => 'Reopened',
                ],
            ],
            'priority' => [
                'type'    => 'select',
                'label'   => 'Prioritas',
                'options' => [
                    'critical' => 'Critical',
                    'high'     => 'High',
                    'medium'   => 'Medium',
                    'low'      => 'Low',
                ],
            ],
            'created_at' => [
                'type'  => 'date_range',
                'label' => 'Tanggal Dibuat',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'id'         => 'ID',
            'title'      => 'Judul',
            'project'    => 'Proyek',
            'priority'   => 'Prioritas',
            'status'     => 'Status',
            'reporter'   => 'Reporter',
            'assignee'   => 'Assignee',
            'sla_due_at' => 'SLA Due',
            'created_at' => 'Dibuat',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = BugTicket::query()->with(['project', 'reporter', 'assignee']);

        $query->whereHas('project', function ($q) use ($user) {
            if (! $user->is_super_admin && $user->company_id) {
                $q->where('company_id', $user->company_id);
            }
        });

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['created_at_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_at_from']);
        }
        if (! empty($filters['created_at_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_at_to']);
        }

        return $query->orderByDesc('id')->get()->map(fn (BugTicket $t) => [
            'id'         => $t->id,
            'title'      => $t->title,
            'project'    => $t->project?->name,
            'priority'   => $t->priority,
            'status'     => $t->status,
            'reporter'   => $t->reporter?->name ?? '-',
            'assignee'   => $t->assignee?->name ?? '-',
            'sla_due_at' => $t->sla_due_at?->format('d/m/Y H:i') ?? '-',
            'created_at' => $t->created_at?->format('d/m/Y H:i'),
        ]);
    }
}
