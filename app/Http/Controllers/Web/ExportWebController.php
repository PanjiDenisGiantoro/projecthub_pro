<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TimesheetExport;
use App\Exports\GenericReportExport;
use App\Exports\ProjectReportExport;

class ExportWebController extends Controller
{
    public function timesheetExcel(Request $request, Project $project)
    {
        $export = new TimesheetExport($project, $request->from, $request->to);
        return Excel::download($export, "detail-log-{$project->id}.xlsx");
    }

    public function timesheetPdf(Request $request, Project $project)
    {
        $logs = TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->when($request->from, fn($q) => $q->whereDate('started_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('started_at', '<=', $request->to))
            ->orderBy('started_at')
            ->get();

        $start = $request->from ? Carbon::parse($request->from) : null;
        $end   = $request->to ? Carbon::parse($request->to) : null;

        $pdf = Pdf::loadView('exports.timesheet_pdf', compact('project', 'logs', 'start', 'end'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("detail-log-{$project->id}.pdf");
    }

    public function timesheetSummaryExcel(Project $project)
    {
        $summary = $this->buildSummary($project);

        $rows = $summary->map(fn($row) => [
            'developer' => $row['user']->name ?? '-',
            'email'     => $row['user']->email ?? '-',
            'jam'       => number_format($row['total_hours'], 2),
            'log'       => $row['logs_count'],
        ]);

        $columns = ['developer' => 'Developer', 'email' => 'Email', 'jam' => 'Total Jam', 'log' => 'Jumlah Log'];

        return Excel::download(
            new GenericReportExport($columns, $rows, 'Ringkasan per Developer'),
            "ringkasan-developer-{$project->id}.xlsx"
        );
    }

    public function timesheetSummaryPdf(Project $project)
    {
        $summary = $this->buildSummary($project);

        $pdf = Pdf::loadView('exports.timesheet_summary_pdf', compact('project', 'summary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("ringkasan-developer-{$project->id}.pdf");
    }

    public function ganttExcel(Project $project)
    {
        $tasks = $this->buildGanttTasks($project);

        $rows = $tasks->map(function ($t) {
            $barStart = $t->start_date ?? $t->due_date ?? $t->sprint?->start_date;
            $barEnd   = $t->due_date   ?? $t->start_date ?? $t->sprint?->end_date;

            return [
                'task'      => $t->title,
                'milestone' => $t->milestone?->title ?? '—',
                'sprint'    => $t->sprint?->name ?? 'Backlog',
                'assignee'  => $t->assignee?->name ?? '—',
                'status'    => ucfirst(str_replace('_', ' ', $t->status)),
                'mulai'     => $barStart?->format('d/m/Y') ?? '—',
                'selesai'   => $barEnd?->format('d/m/Y') ?? '—',
            ];
        });

        $columns = [
            'task' => 'Task', 'milestone' => 'Milestone', 'sprint' => 'Sprint',
            'assignee' => 'Assignee', 'status' => 'Status', 'mulai' => 'Mulai', 'selesai' => 'Selesai',
        ];

        return Excel::download(
            new GenericReportExport($columns, $rows, 'Gantt Chart'),
            "gantt-{$project->id}.xlsx"
        );
    }

    public function ganttPdf(Project $project)
    {
        $ganttTasks = $this->buildGanttTasks($project);

        $allStarts = $ganttTasks->map(fn($t) => $t->start_date ?? $t->due_date ?? $t->sprint?->start_date)->filter();
        $allEnds   = $ganttTasks->map(fn($t) => $t->due_date ?? $t->start_date ?? $t->sprint?->end_date)->filter();
        $ganttStart = $allStarts->min() ?? now()->startOfWeek();
        $ganttEnd   = $allEnds->max()   ?? now()->addDays(30);

        $pdf = Pdf::loadView('exports.gantt_pdf', compact('project', 'ganttTasks', 'ganttStart', 'ganttEnd'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("gantt-{$project->id}.pdf");
    }

    public function projectReportPdf(Project $project)
    {
        $project->load(['manager', 'milestones.tasks', 'tasks', 'members.user', 'risks', 'budgetEntries']);
        $summary = [
            'total_tasks'     => $project->tasks->count(),
            'done_tasks'      => $project->tasks->where('status', 'done')->count(),
            'overdue_tasks'   => $project->tasks->filter(fn($t) => $t->isOverdue())->count(),
            'total_expenses'  => $project->totalExpenses(),
            'budget'          => (float) $project->budget,
        ];

        $pdf = Pdf::loadView('exports.project_report_pdf', compact('project', 'summary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("laporan-{$project->id}.pdf");
    }

    public function projectReportExcel(Project $project)
    {
        return Excel::download(new ProjectReportExport($project), "laporan-{$project->id}.xlsx");
    }

    private function buildSummary(Project $project): Collection
    {
        $logs = TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->get();

        return $logs->groupBy('user_id')->map(fn($ul) => [
            'user'        => $ul->first()->user,
            'total_hours' => round($ul->sum('minutes') / 60, 2),
            'logs_count'  => $ul->count(),
        ])->values();
    }

    private function buildGanttTasks(Project $project): Collection
    {
        return $project->tasks()
            ->with(['milestone', 'assignee', 'sprint'])
            ->where(fn($q) => $q->whereNotNull('start_date')->orWhereNotNull('due_date')->orWhereNotNull('sprint_id'))
            ->orderBy('milestone_id')
            ->orderBy('start_date')
            ->get();
    }
}
