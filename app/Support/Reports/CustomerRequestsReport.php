<?php

namespace App\Support\Reports;

use App\Models\CustomerRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerRequestsReport implements ReportQuery
{
    public function filters(): array
    {
        return [
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => [
                    'submitted'    => 'Submitted',
                    'under_review' => 'Under Review',
                    'approved'     => 'Approved',
                    'rejected'     => 'Rejected',
                    'in_progress'  => 'In Progress',
                    'done'         => 'Done',
                ],
            ],
            'type' => [
                'type'    => 'select',
                'label'   => 'Tipe',
                'options' => [
                    'feature_request'  => 'Feature Request',
                    'bug_report'       => 'Bug Report',
                    'change_request'   => 'Change Request',
                    'general_inquiry'  => 'General Inquiry',
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
            'customer'   => 'Customer',
            'type'       => 'Tipe',
            'priority'   => 'Prioritas',
            'status'     => 'Status',
            'created_at' => 'Dibuat',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = CustomerRequest::query()->with(['project', 'customer']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['created_at_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_at_from']);
        }
        if (! empty($filters['created_at_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_at_to']);
        }

        return $query->orderByDesc('id')->get()->map(fn (CustomerRequest $r) => [
            'id'         => $r->id,
            'title'      => $r->title,
            'project'    => $r->project?->name,
            'customer'   => $r->customer?->name ?? '-',
            'type'       => $r->type,
            'priority'   => $r->priority,
            'status'     => $r->status,
            'created_at' => $r->created_at?->format('d/m/Y H:i'),
        ]);
    }
}
