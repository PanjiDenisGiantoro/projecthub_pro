<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Collection $tickets) {}

    public function collection(): Collection
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return [
            'ID', 'Title', 'Type', 'Priority', 'Status',
            'Reporter', 'Assignee', 'SLA Due', 'SLA Breached',
            'Created At', 'Resolved At', 'Age (days)',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->id,
            $ticket->title,
            $ticket->type,
            $ticket->priority,
            $ticket->status,
            $ticket->reporter?->name,
            $ticket->assignee?->name ?? '-',
            $ticket->sla_due_at?->format('Y-m-d H:i') ?? '-',
            $ticket->sla_breached ? 'Yes' : 'No',
            $ticket->created_at->format('Y-m-d H:i'),
            $ticket->resolved_at?->format('Y-m-d H:i') ?? '-',
            $ticket->created_at->diffInDays(now()),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
