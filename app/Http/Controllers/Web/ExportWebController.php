<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TimesheetExport;
use App\Exports\ProjectReportExport;

class ExportWebController extends Controller
{
    public function timesheetExcel(Request $request, Project $project)
    {
        $month = $request->input('month', now()->format('Y-m'));
        return Excel::download(new TimesheetExport($project, $month), "timesheet-{$project->id}-{$month}.xlsx");
    }

    public function timesheetPdf(Request $request, Project $project)
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $m] = explode('-', $month);
        $start = Carbon::createFromDate($year, $m, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $logs = TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->whereBetween('started_at', [$start, $end])
            ->orderBy('started_at')
            ->get();

        $summary = $logs->groupBy('user_id')->map(fn($g) => [
            'name'    => $g->first()->user?->name,
            'minutes' => $g->sum('minutes'),
        ]);

        $pdf = Pdf::loadView('exports.timesheet_pdf', compact('project', 'logs', 'summary', 'month', 'start', 'end'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("timesheet-{$project->id}-{$month}.pdf");
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
}
