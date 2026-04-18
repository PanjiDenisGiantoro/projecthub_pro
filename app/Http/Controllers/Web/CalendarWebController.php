<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\Milestone;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarWebController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events(Request $request)
    {
        $start  = Carbon::parse($request->input('start', now()->startOfMonth()));
        $end    = Carbon::parse($request->input('end',   now()->endOfMonth()));
        $types  = $request->input('types', ['task', 'milestone', 'sprint', 'ticket']);
        $projId = $request->input('project');

        $user       = auth()->user();
        $isCustomer = $user->hasRole('customer');
        $events     = collect();

        // ── Tasks ─────────────────────────────────────────────────────────────
        if (in_array('task', $types)) {
            $q = Task::with(['project', 'assignee'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$start, $end])
                ->whereNull('deleted_at');

            if ($isCustomer) $q->whereHas('project', fn($q) => $q->where('client_id', $user->id));
            if ($projId)     $q->where('project_id', $projId);

            $priorityColor = ['critical'=>'#EF4444','high'=>'#F97316','medium'=>'#3B82F6','low'=>'#22C55E'];

            $q->get()->each(fn($t) => $events->push([
                'id'    => 'task-' . $t->id,
                'title' => $t->title,
                'start' => $t->due_date->toDateString(),
                'allDay'=> true,
                'color' => $priorityColor[$t->priority ?? 'medium'] ?? '#3B82F6',
                'extendedProps' => [
                    'type'     => 'task',
                    'status'   => $t->status,
                    'priority' => $t->priority,
                    'project'  => $t->project?->name,
                    'assignee' => $t->assignee?->name,
                    'overdue'  => $t->due_date->isPast() && $t->status !== 'done',
                    'url'      => route('tasks.show', [$t->project_id, $t->id]),
                ],
            ]));
        }

        // ── Milestones ────────────────────────────────────────────────────────
        if (in_array('milestone', $types)) {
            $q = Milestone::with('project')
                ->where(fn($q) => $q
                    ->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('due_date', [$start, $end])
                );

            if ($isCustomer) $q->whereHas('project', fn($q) => $q->where('client_id', $user->id));
            if ($projId)     $q->where('project_id', $projId);

            $q->get()->each(fn($m) => $events->push([
                'id'    => 'milestone-' . $m->id,
                'title' => '🏁 ' . $m->title,
                'start' => ($m->start_date ?? $m->due_date)?->toDateString(),
                'end'   => $m->due_date?->addDay()->toDateString(),
                'allDay'=> true,
                'color' => $m->status === 'completed' ? '#8B5CF6' : ($m->isOverdue() ? '#DC2626' : '#7C3AED'),
                'extendedProps' => [
                    'type'    => 'milestone',
                    'status'  => $m->status,
                    'project' => $m->project?->name,
                    'overdue' => $m->isOverdue(),
                    'url'     => route('projects.show', $m->project_id),
                ],
            ]));
        }

        // ── Sprints ───────────────────────────────────────────────────────────
        if (in_array('sprint', $types)) {
            $q = Sprint::with('project')
                ->where(fn($q) => $q
                    ->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn($q) => $q->where('start_date', '<=', $start)->where('end_date', '>=', $end))
                );

            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(fn($s) => $events->push([
                'id'    => 'sprint-' . $s->id,
                'title' => '⚡ ' . $s->name,
                'start' => $s->start_date?->toDateString(),
                'end'   => $s->end_date?->addDay()->toDateString(),
                'allDay'=> true,
                'color' => $s->status === 'completed' ? '#6B7280' : '#10B981',
                'display'=> 'block',
                'extendedProps' => [
                    'type'    => 'sprint',
                    'status'  => $s->status,
                    'project' => $s->project?->name,
                    'url'     => route('sprints.show', [$s->project_id, $s->id]),
                ],
            ]));
        }

        // ── Bug Tickets (SLA due) ─────────────────────────────────────────────
        if (in_array('ticket', $types)) {
            $q = BugTicket::with(['project', 'assignee'])
                ->whereNotNull('sla_due_at')
                ->whereBetween('sla_due_at', [$start, $end])
                ->whereNotIn('status', ['resolved', 'closed']);

            if ($isCustomer) $q->whereHas('project', fn($q) => $q->where('client_id', $user->id));
            if ($projId)     $q->where('project_id', $projId);

            $q->get()->each(fn($t) => $events->push([
                'id'    => 'ticket-' . $t->id,
                'title' => '🐛 ' . $t->title,
                'start' => $t->sla_due_at->toDateString(),
                'allDay'=> true,
                'color' => $t->sla_breached ? '#EF4444' : '#F59E0B',
                'extendedProps' => [
                    'type'     => 'ticket',
                    'status'   => $t->status,
                    'priority' => $t->priority,
                    'project'  => $t->project?->name,
                    'assignee' => $t->assignee?->name,
                    'breached' => $t->sla_breached,
                    'url'      => route('tickets.show', $t->id),
                ],
            ]));
        }

        return response()->json($events->values());
    }

    public function upcoming(Request $request)
    {
        $days   = (int) $request->input('days', 7);
        $start  = now()->startOfDay();
        $end    = now()->addDays($days)->endOfDay();
        $user   = auth()->user();
        $isCustomer = $user->hasRole('customer');

        $items = collect();

        // Upcoming tasks
        $tq = Task::with(['project'])->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->whereNotIn('status', ['done'])->whereNull('deleted_at');
        if ($isCustomer) $tq->whereHas('project', fn($q) => $q->where('client_id', $user->id));
        $tq->get()->each(fn($t) => $items->push([
            'type' => 'task', 'title' => $t->title,
            'date' => $t->due_date->toDateString(),
            'project' => $t->project?->name,
            'priority' => $t->priority,
            'url' => route('tasks.show', [$t->project_id, $t->id]),
        ]));

        // Upcoming milestones
        $mq = Milestone::with(['project'])
            ->whereBetween('due_date', [$start, $end])
            ->where('status', '!=', 'completed');
        if ($isCustomer) $mq->whereHas('project', fn($q) => $q->where('client_id', $user->id));
        $mq->get()->each(fn($m) => $items->push([
            'type' => 'milestone', 'title' => $m->title,
            'date' => $m->due_date?->toDateString(),
            'project' => $m->project?->name,
            'url' => route('projects.show', $m->project_id),
        ]));

        // SLA due tickets
        $tiq = BugTicket::with(['project'])->whereNotNull('sla_due_at')
            ->whereBetween('sla_due_at', [$start, $end])
            ->whereNotIn('status', ['resolved', 'closed']);
        if ($isCustomer) $tiq->whereHas('project', fn($q) => $q->where('client_id', $user->id));
        $tiq->get()->each(fn($t) => $items->push([
            'type' => 'ticket', 'title' => $t->title,
            'date' => $t->sla_due_at->toDateString(),
            'project' => $t->project?->name,
            'priority' => $t->priority,
            'url' => route('tickets.show', $t->id),
        ]));

        return response()->json($items->sortBy('date')->values());
    }
}
