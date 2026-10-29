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
use App\Exports\GanttChartExport;

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

    public function ganttExcel(Request $request, Project $project)
    {
        ['sprints' => $sprints, 'ganttTasks' => $tasks, 'ganttStart' => $ganttStart, 'ganttEnd' => $ganttEnd] = $this->ganttData($project, $request);
        $chart = $this->buildGanttChart($sprints, $tasks, $ganttStart, $ganttEnd);

        return Excel::download(
            new GanttChartExport($project, $chart, $ganttStart, $ganttEnd),
            "gantt-{$project->id}-{$ganttStart->format('Ymd')}-{$ganttEnd->format('Ymd')}.xlsx"
        );
    }

    public function ganttPdf(Request $request, Project $project)
    {
        ['sprints' => $sprints, 'ganttTasks' => $tasks, 'ganttStart' => $ganttStart, 'ganttEnd' => $ganttEnd] = $this->ganttData($project, $request);
        $chart = $this->buildGanttChart($sprints, $tasks, $ganttStart, $ganttEnd);

        $pdf = Pdf::loadView('exports.gantt_pdf', compact('project', 'chart', 'ganttStart', 'ganttEnd'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("gantt-{$project->id}-{$ganttStart->format('Ymd')}-{$ganttEnd->format('Ymd')}.pdf");
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

    /** Sprint & task sama persis dengan tampilan Gantt di tab timesheet (termasuk filter periode). */
    private function ganttData(Project $project, Request $request): array
    {
        $data = app(ProjectWebController::class)->ganttData($project, $request);
        $data['sprints']->load(['milestone', 'lead']);

        return $data;
    }

    /** @return array{0: ?\Carbon\Carbon, 1: ?\Carbon\Carbon, 2: string} mulai, selesai, keterangan */
    private function taskEffectiveDates($t): array
    {
        $start = $t->start_date ?? $t->due_date ?? $t->sprint?->start_date;
        $end   = $t->due_date ?? $t->start_date ?? $t->sprint?->end_date;
        $note  = ($t->start_date || $t->due_date) ? '' : ($start ? 'Tanggal mengikuti sprint' : 'Tanggal belum diisi');

        return [$start, $end, $note];
    }

    /**
     * Susun gantt seperti tampilan web: kolom waktu (per hari, atau per minggu kalau periode > 45 hari),
     * lalu baris grup Sprint + grup per milestone berisi task, lengkap dengan posisi bar per kolom.
     */
    private function buildGanttChart(Collection $sprints, Collection $tasks, \Carbon\CarbonInterface $ganttStart, \Carbon\CarbonInterface $ganttEnd): array
    {
        $dayNames   = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
        $monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $statusColors = ['todo' => '#6366f1', 'in_progress' => '#3b82f6', 'review' => '#a855f7', 'done' => '#22c55e'];

        $byWeek = $ganttStart->diffInDays($ganttEnd) + 1 > 45;
        $units = [];
        $cur = $byWeek ? $ganttStart->copy()->startOfWeek() : $ganttStart->copy()->startOfDay();
        while ($cur->lte($ganttEnd)) {
            $units[] = [
                'start'   => $cur->copy(),
                'month'   => $monthNames[$cur->month] . ' ' . $cur->year,
                'label'   => $byWeek ? $cur->format('d') . '/' . $cur->format('m') : $cur->format('d'),
                'sub'     => $byWeek ? 'Mg ' . $cur->isoWeek : $dayNames[$cur->dayOfWeekIso],
                'weekend' => !$byWeek && $cur->dayOfWeekIso >= 6,
                'today'   => $byWeek ? now()->between($cur, $cur->copy()->endOfWeek()) : $cur->isToday(),
            ];
            $byWeek ? $cur->addWeek() : $cur->addDay();
        }
        $last = count($units) - 1;
        $unitIndex = function (\Carbon\CarbonInterface $d) use ($units, $last) {
            for ($i = $last; $i >= 0; $i--) {
                if ($units[$i]['start']->lte($d)) return $i;
            }
            return -1;
        };
        $bar = function (?\Carbon\CarbonInterface $start, ?\Carbon\CarbonInterface $end) use ($unitIndex, $last, $ganttStart, $ganttEnd) {
            if (!$start && !$end) return null;
            $start = ($start ?? $end)->copy()->startOfDay();
            $end = ($end ?? $start)->copy()->startOfDay();
            if ($end->lt($start)) $end = $start->copy();
            $si = $unitIndex($start);
            $ei = $unitIndex($end);
            if ($ei < 0 || $si > $last) return null;
            return ['from' => max(0, $si), 'to' => min($last, $ei), 'clipStart' => $start->lt($ganttStart), 'clipEnd' => $end->gt($ganttEnd)];
        };
        $fmt = fn (?\Carbon\CarbonInterface $d) => $d?->format('d/m/Y') ?? '—';

        $rows = [];
        if ($sprints->isNotEmpty()) {
            $rows[] = ['kind' => 'group', 'label' => 'Sprint', 'count' => $sprints->count() . ' sprint', 'color' => '#7e22ce', 'bg' => '#faf5ff'];
            foreach ($sprints as $s) {
                $rows[] = [
                    'kind' => 'sprint', 'name' => $s->name, 'sub' => $s->milestone?->title ?? '—', 'pic' => $s->lead?->name ?? '—',
                    'status' => $s->statusLabel(), 'start' => $fmt($s->start_date), 'end' => $fmt($s->end_date),
                    'note' => ($s->start_date && $s->end_date) ? '' : 'Tanggal belum diisi',
                    'bar' => ($s->start_date && $s->end_date) ? $bar($s->start_date, $s->end_date) : null,
                    'color' => '#9333ea', 'estimated' => false,
                ];
            }
        }
        foreach ($tasks->groupBy(fn ($t) => $t->milestone?->title ?? 'Tanpa Milestone') as $milestone => $mTasks) {
            $rows[] = ['kind' => 'group', 'label' => $milestone, 'count' => $mTasks->count() . ' task', 'color' => '#1d4ed8', 'bg' => '#eff6ff'];
            foreach ($mTasks as $t) {
                [$start, $end, $note] = $this->taskEffectiveDates($t);
                $rows[] = [
                    'kind' => 'task', 'name' => $t->title, 'sub' => $t->sprint?->name ?? 'Backlog', 'pic' => $t->assignee?->name ?? '—',
                    'status' => ucfirst(str_replace('_', ' ', $t->status)), 'start' => $fmt($start), 'end' => $fmt($end),
                    'note' => $note, 'bar' => $bar($start, $end),
                    'color' => $statusColors[$t->status] ?? '#6366f1', 'estimated' => $note !== '',
                ];
            }
        }

        return ['units' => $units, 'byWeek' => $byWeek, 'rows' => $rows, 'legend' => [
            'Sprint' => '#9333ea', 'To Do' => '#6366f1', 'In Progress' => '#3b82f6', 'Review' => '#a855f7', 'Done' => '#22c55e',
        ]];
    }
}
