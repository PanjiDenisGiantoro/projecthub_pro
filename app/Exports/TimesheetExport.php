<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\TimeLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimesheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        private Project $project,
        private ?string $from = null,
        private ?string $to = null,
    ) {}

    public function collection()
    {
        return TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $this->project->id))
            ->when($this->from, fn($q) => $q->whereDate('started_at', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('started_at', '<=', $this->to))
            ->orderBy('started_at')
            ->get()
            ->map(fn($log) => [
                'Tanggal'     => $log->started_at?->format('d/m/Y'),
                'Staff'       => $log->user?->name,
                'Task'        => $log->task?->title,
                'Menit'       => $log->minutes,
                'Jam'         => round($log->minutes / 60, 2),
                'Catatan'     => $log->notes,
            ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Staff', 'Task', 'Menit', 'Jam', 'Catatan'];
    }

    public function title(): string
    {
        return 'Detail Log Waktu';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
