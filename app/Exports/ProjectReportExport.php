<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectReportExport implements WithMultipleSheets
{
    public function __construct(private Project $project) {}

    public function sheets(): array
    {
        return [
            new ProjectTasksSheet($this->project),
            new ProjectBudgetSheet($this->project),
        ];
    }
}

class ProjectTasksSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private Project $project) {}

    public function collection()
    {
        return $this->project->tasks()->with('assignee', 'milestone')->get()->map(fn($t) => [
            'ID'         => $t->id,
            'Judul'      => $t->title,
            'Milestone'  => $t->milestone?->title,
            'Assignee'   => $t->assignee?->name,
            'Status'     => $t->status,
            'Prioritas'  => $t->priority,
            'Mulai'      => $t->start_date?->format('d/m/Y'),
            'Selesai'    => $t->due_date?->format('d/m/Y'),
            'Est. Jam'   => $t->estimated_hours,
            'Story Pts'  => $t->story_points,
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Judul', 'Milestone', 'Assignee', 'Status', 'Prioritas', 'Mulai', 'Selesai', 'Est. Jam', 'Story Pts'];
    }

    public function title(): string { return 'Tasks'; }
}

class ProjectBudgetSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private Project $project) {}

    public function collection()
    {
        return $this->project->budgetEntries()->with('creator')->orderBy('entry_date')->get()->map(fn($e) => [
            'Tanggal'    => $e->entry_date?->format('d/m/Y'),
            'Tipe'       => $e->type,
            'Kategori'   => $e->category,
            'Deskripsi'  => $e->description,
            'Jumlah'     => $e->amount,
            'Referensi'  => $e->reference,
            'Oleh'       => $e->creator?->name,
        ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'Kategori', 'Deskripsi', 'Jumlah', 'Referensi', 'Oleh'];
    }

    public function title(): string { return 'Anggaran'; }
}
