<?php

namespace App\Support\Reports;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Collection;

class InvoicesReport implements ReportQuery
{
    public function filters(): array
    {
        return [
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => [
                    'draft'     => 'Draft',
                    'sent'      => 'Terkirim',
                    'paid'      => 'Lunas',
                    'overdue'   => 'Jatuh Tempo',
                    'cancelled' => 'Dibatalkan',
                ],
            ],
            'issue_date' => [
                'type'  => 'date_range',
                'label' => 'Tanggal Terbit',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'id'             => 'ID',
            'invoice_number' => 'No. Invoice',
            'project'        => 'Proyek',
            'client'         => 'Klien',
            'status'         => 'Status',
            'issue_date'     => 'Tanggal Terbit',
            'due_date'       => 'Jatuh Tempo',
            'total'          => 'Total',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Invoice::query()->with(['project', 'client']);

        if (! $user->is_super_admin && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['issue_date_from'])) {
            $query->whereDate('issue_date', '>=', $filters['issue_date_from']);
        }
        if (! empty($filters['issue_date_to'])) {
            $query->whereDate('issue_date', '<=', $filters['issue_date_to']);
        }

        return $query->orderByDesc('id')->get()->map(fn (Invoice $i) => [
            'id'             => $i->id,
            'invoice_number' => $i->invoice_number,
            'project'        => $i->project?->name,
            'client'         => $i->client?->name ?? '-',
            'status'         => $i->status,
            'issue_date'     => $i->issue_date?->format('d/m/Y'),
            'due_date'       => $i->due_date?->format('d/m/Y'),
            'total'          => number_format((float) $i->total, 0, ',', '.'),
        ]);
    }
}
