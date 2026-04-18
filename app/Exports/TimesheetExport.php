<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\TimeLog;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimesheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        private Project $project,
        private string $month
    ) {}

    public function collection()
    {
        [$year, $m] = explode('-', $this->month);
        $start = Carbon::createFromDate($year, $m, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        return TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $this->project->id))
            ->whereBetween('started_at', [$start, $end])
            ->orderBy('started_at')
            ->get()
            ->map(fn($log) => [
                'Tanggal'     => $log->started_at?->format('d/m/Y'),
                'Staff'       => $log->user?->name,
                'Task'        => $log->task?->title,
                'Menit'       => $log->minutes,
                'Jam'         => round($log->minutes / 60, 2),
                'Catatan'     => $log->note,
            ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Staff', 'Task', 'Menit', 'Jam', 'Catatan'];
    }

    public function title(): string
    {
        return 'Timesheet ' . $this->month;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
