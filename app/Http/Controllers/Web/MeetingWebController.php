<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\Request;

class MeetingWebController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->input('category', 'all');
        $when     = $request->input('when', 'upcoming'); // upcoming|past|all
        $projId   = $request->input('project');

        $user       = auth()->user();
        $isCustomer = $user->hasRole('customer');

        $scopeToClient = function ($q) use ($isCustomer, $user) {
            if ($isCustomer) $q->where('client_id', $user->id);
        };

        // Always query every category so tab badge counts stay accurate
        // regardless of which tab is currently selected; filter for display below.
        $meetings = collect();

        // ── Projects ─────────────────────────────────────────────────────────
        {
            $q = Project::query()
                ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'));
            $scopeToClient($q);
            if ($projId) $q->where('id', $projId);

            $q->get()->each(fn($p) => $meetings->push([
                'type'       => 'project',
                'title'      => $p->name,
                'project'    => $p->name,
                'startsAt'   => $p->meeting_starts_at,
                'meetLink'   => $p->google_meet_link,
                'recurring'  => false,
                'organizer'  => null,
                'url'        => route('projects.show', $p->id),
                'createUrl'  => route('projects.meeting.create', $p->id),
            ]));
        }

        // ── Sprints ──────────────────────────────────────────────────────────
        {
            $q = Sprint::with(['project', 'meetingOrganizer'])
                ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'));
            if ($isCustomer) $q->whereHas('project', $scopeToClient);
            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(fn($s) => $meetings->push([
                'type'       => 'sprint',
                'title'      => $s->name,
                'project'    => $s->project?->name,
                'startsAt'   => $s->meeting_starts_at,
                'meetLink'   => $s->google_meet_link,
                'recurring'  => (bool) $s->google_meeting_is_recurring,
                'organizer'  => $s->meetingOrganizer?->name,
                'url'        => route('sprints.show', [$s->project_id, $s->id]),
                'createUrl'  => route('sprints.meeting.create', [$s->project_id, $s->id]),
            ]));
        }

        // ── Milestones ───────────────────────────────────────────────────────
        {
            $q = Milestone::with(['project', 'meetingOrganizer'])
                ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'));
            if ($isCustomer) $q->whereHas('project', $scopeToClient);
            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(fn($m) => $meetings->push([
                'type'       => 'milestone',
                'title'      => $m->title,
                'project'    => $m->project?->name,
                'startsAt'   => $m->meeting_starts_at,
                'meetLink'   => $m->google_meet_link,
                'recurring'  => false,
                'organizer'  => $m->meetingOrganizer?->name,
                'url'        => route('projects.show', $m->project_id),
                'createUrl'  => route('milestones.meeting.create', [$m->project_id, $m->id]),
            ]));
        }

        // ── Tasks ────────────────────────────────────────────────────────────
        {
            $q = Task::with(['project', 'meetingOrganizer'])
                ->whereNull('deleted_at')
                ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'));
            if ($isCustomer) $q->whereHas('project', $scopeToClient);
            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(fn($t) => $meetings->push([
                'type'       => 'task',
                'title'      => $t->title,
                'project'    => $t->project?->name,
                'startsAt'   => $t->meeting_starts_at,
                'meetLink'   => $t->google_meet_link,
                'recurring'  => false,
                'organizer'  => $t->meetingOrganizer?->name,
                'url'        => route('tasks.show', [$t->project_id, $t->id]),
                'createUrl'  => route('tasks.meeting.create', [$t->project_id, $t->id]),
            ]));
        }

        // ── Bug Tickets ──────────────────────────────────────────────────────
        {
            $q = BugTicket::with(['project'])
                ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'));
            if ($isCustomer) $q->whereHas('project', $scopeToClient);
            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(fn($t) => $meetings->push([
                'type'       => 'ticket',
                'title'      => $t->title,
                'project'    => $t->project?->name,
                'startsAt'   => $t->meeting_starts_at,
                'meetLink'   => $t->google_meet_link,
                'recurring'  => false,
                'organizer'  => null,
                'url'        => route('tickets.show', $t->id),
                'createUrl'  => route('tickets.meeting.create', $t->id),
            ]));
        }

        // ── Filter by time window (applies to all tabs, independent of category) ──
        $now = now();
        if ($when === 'upcoming') {
            $meetings = $meetings->filter(fn($m) => !$m['startsAt'] || $m['startsAt']->isFuture());
        } elseif ($when === 'past') {
            $meetings = $meetings->filter(fn($m) => $m['startsAt'] && $m['startsAt']->isPast());
        }

        $meetings = $meetings->sortBy(fn($m) => $m['startsAt'] ?? $now->copy()->addCentury())->values();

        // Counts reflect the current time window across ALL categories, so tab
        // badges stay correct no matter which tab is currently selected.
        $counts = [
            'all'       => $meetings->count(),
            'project'   => $meetings->where('type', 'project')->count(),
            'sprint'    => $meetings->where('type', 'sprint')->count(),
            'milestone' => $meetings->where('type', 'milestone')->count(),
            'task'      => $meetings->where('type', 'task')->count(),
            'ticket'    => $meetings->where('type', 'ticket')->count(),
        ];

        // Now narrow down to the selected category for display.
        if ($category !== 'all') {
            $meetings = $meetings->where('type', $category)->values();
        }

        $projects = $isCustomer
            ? Project::where('client_id', $user->id)->orderBy('name')->get(['id', 'name'])
            : Project::orderBy('name')->get(['id', 'name']);

        return view('meetings.index', [
            'meetings'  => $meetings,
            'counts'    => $counts,
            'category'  => $category,
            'when'      => $when,
            'projects'  => $projects,
            'projectId' => $projId,
        ]);
    }
}
