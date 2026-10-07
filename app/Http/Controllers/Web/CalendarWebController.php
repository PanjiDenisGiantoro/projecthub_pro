<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\CalendarEvent;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CalendarWebController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isCustomer = $user->hasRole('client');

        $usersQuery = User::query()->where('is_active', true);
        if (! $user->is_super_admin && $user->company_id) {
            $usersQuery->where('company_id', $user->company_id);
        }
        $members = $usersQuery->orderBy('name')->get(['id', 'name', 'email', 'avatar']);

        $categories = ['Meeting', 'Task', 'Sprint', 'Milestone', 'Personal', 'Reminder'];

        $colors = [
            ['name' => 'Blue',   'value' => 'blue',   'bg' => 'bg-blue-500',   'text' => 'text-blue-400',   'hex' => '#3B82F6'],
            ['name' => 'Green',  'value' => 'green',  'bg' => 'bg-green-500',  'text' => 'text-green-400',  'hex' => '#10B981'],
            ['name' => 'Purple', 'value' => 'purple', 'bg' => 'bg-purple-500', 'text' => 'text-purple-400', 'hex' => '#8B5CF6'],
            ['name' => 'Orange', 'value' => 'orange', 'bg' => 'bg-orange-500', 'text' => 'text-orange-400', 'hex' => '#F97316'],
            ['name' => 'Pink',   'value' => 'pink',   'bg' => 'bg-pink-500',   'text' => 'text-pink-400',   'hex' => '#EC4899'],
            ['name' => 'Red',    'value' => 'red',    'bg' => 'bg-red-500',    'text' => 'text-red-400',    'hex' => '#EF4444'],
        ];

        $availableTags = ['Important', 'Urgent', 'Work', 'Personal', 'Team', 'Client'];

        $hasGoogleConnected = (bool) ($user->googleToken()->exists());
        $googleConnectUrl   = route('google-calendar.connect');

        return view('calendar.index', compact('members', 'categories', 'colors', 'availableTags', 'hasGoogleConnected', 'googleConnectUrl'));
    }

    public function events(Request $request)
    {
        $startInput = $request->input('start');
        $endInput   = $request->input('end');

        // Rentang default jika tidak dikirim: 6 bulan ke belakang sampai 1 tahun ke depan
        $start = $startInput ? Carbon::parse($startInput) : now()->subMonths(6)->startOfDay();
        $end   = $endInput   ? Carbon::parse($endInput)   : now()->addYear()->endOfDay();

        $types  = $request->input('types', ['custom', 'task', 'milestone', 'sprint', 'ticket']);
        $projInput = $request->input('project');
        $projId = null;
        if ($projInput) {
            $resolvedProject = Project::where('slug', $projInput)->orWhere('id', is_numeric($projInput) ? (int)$projInput : 0)->first();
            $projId = $resolvedProject?->id;
        }

        $user       = auth()->user();
        $isCustomer = $user->hasRole('client');
        $events     = collect();

        // Tenant scope
        $companyScope = function ($q) use ($user, $isCustomer) {
            if (! $user->is_super_admin && $user->company_id) {
                $q->where('company_id', $user->company_id);
            }
            if ($isCustomer) {
                $q->where('client_id', $user->id);
            }
        };

        // ── 1. Custom Calendar Events (termasuk meeting mandiri & invitees) ───────
        if (in_array('custom', $types)) {
            $cq = CalendarEvent::with(['creator', 'attendees'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                      ->orWhereBetween('end_time', [$start, $end])
                      ->orWhere(fn($sub) => $sub->where('start_time', '<=', $start)->where('end_time', '>=', $end));
                });

            // Hanya lihat event jika creator, salah satu attendee, atau dalam 1 company (bukan client)
            if (! $user->is_super_admin) {
                $cq->where(function ($q) use ($user) {
                    $q->where('creator_id', $user->id)
                      ->orWhereHas('attendees', fn($att) => $att->where('user_id', $user->id))
                      ->orWhere(function ($comp) use ($user) {
                          if ($user->company_id) {
                              $comp->where('company_id', $user->company_id);
                          }
                      });
                });
            }

            $cq->get()->each(function ($ce) use ($events, $user) {
                $canEdit = $user->is_super_admin || $ce->creator_id === $user->id;
                $events->push([
                    'id'               => 'custom-' . $ce->id,
                    'raw_id'           => $ce->id,
                    'source'           => 'custom',
                    'title'            => $ce->title,
                    'description'      => $ce->description ?? '',
                    'startTime'        => $ce->start_time->toISOString(),
                    'endTime'          => $ce->end_time->toISOString(),
                    'start'            => $ce->start_time->toISOString(),
                    'end'              => $ce->end_time->toISOString(),
                    'color'            => $ce->color ?? 'blue',
                    'category'         => $ce->category ?? 'Meeting',
                    'tags'             => $ce->tags ?? [],
                    'google_meet_link' => $ce->google_meet_link,
                    'creator'          => [
                        'id'   => $ce->creator?->id,
                        'name' => $ce->creator?->name,
                    ],
                    'attendees'        => $ce->attendees->map(fn($a) => [
                        'id'     => $a->id,
                        'name'   => $a->name,
                        'email'  => $a->email,
                        'avatar' => $a->avatar,
                    ])->values()->all(),
                    'attendee_ids'     => $ce->attendees->pluck('id')->values()->all(),
                    'can_edit'         => $canEdit,
                    'url'              => null,
                ]);
            });
        }

        // ── 2. Tasks ─────────────────────────────────────────────────────────────
        if (in_array('task', $types)) {
            $q = Task::with(['project', 'assignee', 'meetingOrganizer'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
                ->whereNull('deleted_at')
                ->whereHas('project', $companyScope);

            if ($projId) $q->where('project_id', $projId);

            $priorityColor = ['critical' => 'red', 'urgent' => 'red', 'high' => 'orange', 'medium' => 'blue', 'low' => 'green'];

            $q->get()->each(function ($t) use ($events, $priorityColor) {
                $startDt = Carbon::parse($t->due_date)->setTime(9, 0);
                $endDt   = Carbon::parse($t->due_date)->setTime(10, 0);

                $tags = ['Task'];
                if ($t->priority) $tags[] = ucfirst($t->priority);
                if ($t->project?->name) $tags[] = $t->project->name;

                $events->push([
                    'id'               => 'task-' . $t->id,
                    'raw_id'           => $t->id,
                    'source'           => 'task',
                    'title'            => $t->title,
                    'description'      => $t->description ?? ('Task on ' . ($t->project?->name ?? 'Project')),
                    'startTime'        => $startDt->toISOString(),
                    'endTime'          => $endDt->toISOString(),
                    'start'            => $t->due_date->toDateString(),
                    'end'              => $t->due_date->toDateString(),
                    'color'            => $priorityColor[$t->priority ?? 'medium'] ?? 'blue',
                    'category'         => 'Task',
                    'tags'             => $tags,
                    'google_meet_link' => $t->google_meet_link,
                    'creator'          => null,
                    'attendees'        => $t->assignee ? [[
                        'id'     => $t->assignee->id,
                        'name'   => $t->assignee->name,
                        'email'  => $t->assignee->email,
                        'avatar' => $t->assignee->avatar,
                    ]] : [],
                    'attendee_ids'     => $t->assignee ? [$t->assignee->id] : [],
                    'can_edit'         => false,
                    'url'              => $t->project ? route('tasks.show', [$t->project, $t->id]) : route('tasks.show', [$t->project_id, $t->id]),
                ]);

                // Jika task punya jadwal meeting mandiri
                if ($t->meeting_starts_at && $t->google_meet_link) {
                    $mStart = Carbon::parse($t->meeting_starts_at);
                    $mEnd   = $mStart->copy()->addHour();
                    $events->push([
                        'id'               => 'meeting-task-' . $t->id,
                        'raw_id'           => $t->id,
                        'source'           => 'task_meeting',
                        'title'            => 'Meet: ' . $t->title,
                        'description'      => 'Meeting for task ' . $t->title . ' (' . ($t->project?->name ?? '') . ')',
                        'startTime'        => $mStart->toISOString(),
                        'endTime'          => $mEnd->toISOString(),
                        'start'            => $mStart->toISOString(),
                        'end'              => $mEnd->toISOString(),
                        'color'            => 'blue',
                        'category'         => 'Meeting',
                        'tags'             => ['Meeting', 'Work', 'Team'],
                        'google_meet_link' => $t->google_meet_link,
                        'creator'          => $t->meetingOrganizer ? ['id' => $t->meetingOrganizer->id, 'name' => $t->meetingOrganizer->name] : null,
                        'attendees'        => $t->assignee ? [['id' => $t->assignee->id, 'name' => $t->assignee->name]] : [],
                        'attendee_ids'     => $t->assignee ? [$t->assignee->id] : [],
                        'can_edit'         => false,
                        'url'              => $t->project ? route('tasks.show', [$t->project, $t->id]) : route('tasks.show', [$t->project_id, $t->id]),
                    ]);
                }
            });
        }

        // ── 3. Milestones ─────────────────────────────────────────────────────────
        if (in_array('milestone', $types)) {
            $q = Milestone::with(['project', 'assignee', 'meetingOrganizer'])
                ->where(fn($q) => $q
                    ->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('due_date', [$start, $end])
                )
                ->whereHas('project', $companyScope);

            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(function ($m) use ($events) {
                $startDt = $m->start_date ? Carbon::parse($m->start_date)->startOfDay() : ($m->due_date ? Carbon::parse($m->due_date)->startOfDay() : now());
                $endDt   = $m->due_date ? Carbon::parse($m->due_date)->endOfDay() : $startDt->copy()->endOfDay();

                $tags = ['Milestone', 'Work'];
                if ($m->project?->name) $tags[] = $m->project->name;

                $events->push([
                    'id'               => 'milestone-' . $m->id,
                    'raw_id'           => $m->id,
                    'source'           => 'milestone',
                    'title'            => '🏁 ' . $m->title,
                    'description'      => $m->description ?? ('Milestone on ' . ($m->project?->name ?? 'Project')),
                    'startTime'        => $startDt->toISOString(),
                    'endTime'          => $endDt->toISOString(),
                    'start'            => $startDt->toDateString(),
                    'end'              => $endDt->toDateString(),
                    'color'            => 'purple',
                    'category'         => 'Milestone',
                    'tags'             => $tags,
                    'google_meet_link' => $m->google_meet_link,
                    'creator'          => null,
                    'attendees'        => $m->assignee ? [[
                        'id'     => $m->assignee->id,
                        'name'   => $m->assignee->name,
                        'email'  => $m->assignee->email,
                        'avatar' => $m->assignee->avatar,
                    ]] : [],
                    'attendee_ids'     => $m->assignee ? [$m->assignee->id] : [],
                    'can_edit'         => false,
                    'url'              => $m->project ? route('projects.tab', [$m->project, 'tasks']) . '?milestone=' . $m->id : ($m->project_id ? route('projects.show', $m->project_id) : '#'),
                ]);

                if ($m->meeting_starts_at && $m->google_meet_link) {
                    $mStart = Carbon::parse($m->meeting_starts_at);
                    $mEnd   = $mStart->copy()->addHour();
                    $events->push([
                        'id'               => 'meeting-milestone-' . $m->id,
                        'raw_id'           => $m->id,
                        'source'           => 'milestone_meeting',
                        'title'            => 'Meet: ' . $m->title,
                        'description'      => 'Milestone review for ' . $m->title,
                        'startTime'        => $mStart->toISOString(),
                        'endTime'          => $mEnd->toISOString(),
                        'start'            => $mStart->toISOString(),
                        'end'              => $mEnd->toISOString(),
                        'color'            => 'purple',
                        'category'         => 'Meeting',
                        'tags'             => ['Meeting', 'Milestone', 'Client'],
                        'google_meet_link' => $m->google_meet_link,
                        'creator'          => $m->meetingOrganizer ? ['id' => $m->meetingOrganizer->id, 'name' => $m->meetingOrganizer->name] : null,
                        'attendees'        => [],
                        'attendee_ids'     => [],
                        'can_edit'         => false,
                        'url'              => $m->project ? route('projects.tab', [$m->project, 'tasks']) . '?milestone=' . $m->id : ($m->project_id ? route('projects.show', $m->project_id) : '#'),
                    ]);
                }
            });
        }

        // ── 4. Sprints ───────────────────────────────────────────────────────────
        if (in_array('sprint', $types)) {
            $q = Sprint::with(['project', 'meetingOrganizer'])
                ->where(fn($q) => $q
                    ->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn($sub) => $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end))
                )
                ->whereHas('project', $companyScope);

            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(function ($s) use ($events) {
                $startDt = $s->start_date ? Carbon::parse($s->start_date)->startOfDay() : now()->startOfDay();
                $endDt   = $s->end_date ? Carbon::parse($s->end_date)->endOfDay() : $startDt->copy()->endOfDay();

                $tags = ['Sprint', 'Team', 'Work'];
                if ($s->project?->name) $tags[] = $s->project->name;

                $events->push([
                    'id'               => 'sprint-' . $s->id,
                    'raw_id'           => $s->id,
                    'source'           => 'sprint',
                    'title'            => '⚡ ' . $s->name,
                    'description'      => $s->description ?? ('Sprint on ' . ($s->project?->name ?? 'Project')),
                    'startTime'        => $startDt->toISOString(),
                    'endTime'          => $endDt->toISOString(),
                    'start'            => $startDt->toDateString(),
                    'end'              => $endDt->toDateString(),
                    'color'            => 'green',
                    'category'         => 'Sprint',
                    'tags'             => $tags,
                    'google_meet_link' => $s->google_meet_link,
                    'creator'          => null,
                    'attendees'        => [],
                    'attendee_ids'     => [],
                    'can_edit'         => false,
                    'url'              => $s->project ? route('projects.tab', [$s->project, 'tasks']) . '?sprint=' . $s->id : (($s->project ?? $s->project_id) ? route('sprints.show', [$s->project ?? $s->project_id, $s->id]) : '#'),
                ]);

                if ($s->meeting_starts_at && $s->google_meet_link) {
                    $mStart = Carbon::parse($s->meeting_starts_at);
                    $mEnd   = $mStart->copy()->addHour();
                    $events->push([
                        'id'               => 'meeting-sprint-' . $s->id,
                        'raw_id'           => $s->id,
                        'source'           => 'sprint_meeting',
                        'title'            => 'Meet: ' . $s->name,
                        'description'      => 'Sprint meeting for ' . $s->name,
                        'startTime'        => $mStart->toISOString(),
                        'endTime'          => $mEnd->toISOString(),
                        'start'            => $mStart->toISOString(),
                        'end'              => $mEnd->toISOString(),
                        'color'            => 'green',
                        'category'         => 'Meeting',
                        'tags'             => ['Meeting', 'Sprint', 'Team'],
                        'google_meet_link' => $s->google_meet_link,
                        'creator'          => $s->meetingOrganizer ? ['id' => $s->meetingOrganizer->id, 'name' => $s->meetingOrganizer->name] : null,
                        'attendees'        => [],
                        'attendee_ids'     => [],
                        'can_edit'         => false,
                        'url'              => $s->project ? route('projects.tab', [$s->project, 'tasks']) . '?sprint=' . $s->id : (($s->project ?? $s->project_id) ? route('sprints.show', [$s->project ?? $s->project_id, $s->id]) : '#'),
                    ]);
                }
            });
        }

        // ── 5. Bug Tickets ───────────────────────────────────────────────────────
        if (in_array('ticket', $types)) {
            $q = BugTicket::with(['project', 'assignee'])
                ->whereNotNull('sla_due_at')
                ->whereBetween('sla_due_at', [$start, $end])
                ->whereNotIn('status', ['resolved', 'closed'])
                ->whereHas('project', $companyScope);

            if ($projId) $q->where('project_id', $projId);

            $q->get()->each(function ($t) use ($events) {
                $startDt = Carbon::parse($t->sla_due_at);
                $endDt   = $startDt->copy()->addHour();

                $tags = ['Urgent', 'Ticket'];
                if ($t->project?->name) $tags[] = $t->project->name;

                $events->push([
                    'id'               => 'ticket-' . $t->id,
                    'raw_id'           => $t->id,
                    'source'           => 'ticket',
                    'title'            => '🐛 ' . $t->title,
                    'description'      => $t->description ?? ('SLA Due Bug Ticket: ' . $t->title),
                    'startTime'        => $startDt->toISOString(),
                    'endTime'          => $endDt->toISOString(),
                    'start'            => $startDt->toDateString(),
                    'end'              => $endDt->toDateString(),
                    'color'            => $t->sla_breached ? 'red' : 'orange',
                    'category'         => 'Task',
                    'tags'             => $tags,
                    'google_meet_link' => $t->google_meet_link,
                    'creator'          => null,
                    'attendees'        => $t->assignee ? [[
                        'id'     => $t->assignee->id,
                        'name'   => $t->assignee->name,
                        'email'  => $t->assignee->email,
                        'avatar' => $t->assignee->avatar,
                    ]] : [],
                    'attendee_ids'     => $t->assignee ? [$t->assignee->id] : [],
                    'can_edit'         => false,
                    'url'              => route('tickets.show', $t->id),
                ]);
            });
        }

        return response()->json($events->values());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after_or_equal:start_time',
            'category'    => 'nullable|string|max:50',
            'color'       => 'nullable|string|max:20',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
            'attendees'   => 'nullable|array',
            'attendees.*' => 'integer|exists:users,id',
        ]);

        $user = auth()->user();
        $category = $validated['category'] ?? 'Meeting';

        // Validasi: Wajib connect Google Calendar jika kategori adalah Meeting
        if (strtolower($category) === 'meeting') {
            if (! $user->googleToken()->exists()) {
                return response()->json([
                    'ok'                  => false,
                    'message'             => 'Akun Anda belum terhubung dengan Google Calendar. Silakan hubungkan Google Calendar terlebih dahulu agar link Google Meet dapat dibuat secara otomatis.',
                    'need_google_connect' => true,
                    'connect_url'         => route('google-calendar.connect'),
                    'errors'              => [
                        'category' => ['Akun Anda belum terhubung ke Google Calendar. Silakan hubungkan Google Calendar terlebih dahulu.'],
                    ],
                ], 422);
            }
        }

        $meetLink = null;
        $googleEventId = null;

        if (strtolower($category) === 'meeting') {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $attendeeUsers = ! empty($validated['attendees'])
                    ? User::whereIn('id', $validated['attendees'])->get()->all()
                    : [];

                $res = $calendarService->createStandaloneMeeting(
                    actor: $user,
                    title: $validated['title'],
                    description: $validated['description'] ?? null,
                    start: Carbon::parse($validated['start_time']),
                    end: Carbon::parse($validated['end_time']),
                    attendees: $attendeeUsers,
                );

                $meetLink = $res['meet_link'] ?? null;
                $googleEventId = $res['event_id'] ?? null;
            } catch (\RuntimeException $e) {
                return response()->json([
                    'ok'                  => false,
                    'message'             => $e->getMessage(),
                    'need_google_connect' => true,
                    'connect_url'         => route('google-calendar.connect'),
                    'errors'              => ['category' => [$e->getMessage()]],
                ], 422);
            } catch (\Throwable $e) {
                \Log::error('Failed creating Google Calendar standalone meeting: ' . $e->getMessage());
                return response()->json([
                    'ok'                  => false,
                    'message'             => 'Gagal membuat Google Meet: ' . $e->getMessage() . '. Pastikan izin Google Calendar akun Anda aktif.',
                    'need_google_connect' => true,
                    'connect_url'         => route('google-calendar.connect'),
                ], 422);
            }
        }

        $event = CalendarEvent::create([
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'start_time'       => Carbon::parse($validated['start_time']),
            'end_time'         => Carbon::parse($validated['end_time']),
            'category'         => $category,
            'color'            => $validated['color'] ?? 'blue',
            'tags'             => $validated['tags'] ?? [],
            'google_meet_link' => $meetLink,
            'google_event_id'  => $googleEventId,
            'creator_id'       => $user->id,
            'company_id'       => $user->company_id,
        ]);

        if (! empty($validated['attendees'])) {
            $event->attendees()->sync($validated['attendees']);
        }

        $event->load(['creator', 'attendees']);

        return response()->json([
            'ok'      => true,
            'message' => 'Event berhasil dibuat.',
            'event'   => [
                'id'               => 'custom-' . $event->id,
                'raw_id'           => $event->id,
                'source'           => 'custom',
                'title'            => $event->title,
                'description'      => $event->description ?? '',
                'startTime'        => $event->start_time->toISOString(),
                'endTime'          => $event->end_time->toISOString(),
                'start'            => $event->start_time->toISOString(),
                'end'              => $event->end_time->toISOString(),
                'color'            => $event->color,
                'category'         => $event->category,
                'tags'             => $event->tags ?? [],
                'google_meet_link' => $event->google_meet_link,
                'creator'          => [
                    'id'   => $event->creator?->id,
                    'name' => $event->creator?->name,
                ],
                'attendees'        => $event->attendees->map(fn($a) => [
                    'id'     => $a->id,
                    'name'   => $a->name,
                    'email'  => $a->email,
                    'avatar' => $a->avatar,
                ])->values()->all(),
                'attendee_ids'     => $event->attendees->pluck('id')->values()->all(),
                'can_edit'         => true,
            ],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $event = CalendarEvent::findOrFail($id);
        $user  = auth()->user();

        if (! $user->is_super_admin && $event->creator_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah event ini.');
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after_or_equal:start_time',
            'category'    => 'nullable|string|max:50',
            'color'       => 'nullable|string|max:20',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
            'attendees'   => 'nullable|array',
            'attendees.*' => 'integer|exists:users,id',
        ]);

        $category = $validated['category'] ?? $event->category;
        $meetLink = $event->google_meet_link;
        $googleEventId = $event->google_event_id;

        // Validasi jika diubah ke Meeting dan belum punya Google Meet link
        if (strtolower($category) === 'meeting' && ! $meetLink) {
            if (! $user->googleToken()->exists()) {
                return response()->json([
                    'ok'                  => false,
                    'message'             => 'Akun Anda belum terhubung dengan Google Calendar. Silakan hubungkan Google Calendar terlebih dahulu agar link Google Meet dapat dibuat secara otomatis.',
                    'need_google_connect' => true,
                    'connect_url'         => route('google-calendar.connect'),
                    'errors'              => [
                        'category' => ['Akun Anda belum terhubung ke Google Calendar. Silakan hubungkan Google Calendar terlebih dahulu.'],
                    ],
                ], 422);
            }

            try {
                $calendarService = app(GoogleCalendarService::class);
                $attendeeUsers = array_key_exists('attendees', $validated)
                    ? (! empty($validated['attendees']) ? User::whereIn('id', $validated['attendees'])->get()->all() : [])
                    : $event->attendees()->get()->all();

                $res = $calendarService->createStandaloneMeeting(
                    actor: $user,
                    title: $validated['title'],
                    description: $validated['description'] ?? null,
                    start: Carbon::parse($validated['start_time']),
                    end: Carbon::parse($validated['end_time']),
                    attendees: $attendeeUsers,
                );

                $meetLink = $res['meet_link'] ?? null;
                $googleEventId = $res['event_id'] ?? null;
            } catch (\RuntimeException $e) {
                return response()->json([
                    'ok'                  => false,
                    'message'             => $e->getMessage(),
                    'need_google_connect' => true,
                    'connect_url'         => route('google-calendar.connect'),
                    'errors'              => ['category' => [$e->getMessage()]],
                ], 422);
            } catch (\Throwable $e) {
                \Log::error('Failed creating Google Meet standalone update: ' . $e->getMessage());
                return response()->json([
                    'ok'                  => false,
                    'message'             => 'Gagal membuat Google Meet: ' . $e->getMessage() . '. Pastikan izin Google Calendar akun Anda aktif.',
                    'need_google_connect' => true,
                    'connect_url'         => route('google-calendar.connect'),
                ], 422);
            }
        }

        $event->update([
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'start_time'       => Carbon::parse($validated['start_time']),
            'end_time'         => Carbon::parse($validated['end_time']),
            'category'         => $category,
            'color'            => $validated['color'] ?? $event->color,
            'tags'             => $validated['tags'] ?? [],
            'google_meet_link' => $meetLink,
            'google_event_id'  => $googleEventId,
        ]);

        if (array_key_exists('attendees', $validated)) {
            $event->attendees()->sync($validated['attendees'] ?? []);
        }

        $event->load(['creator', 'attendees']);

        return response()->json([
            'ok'      => true,
            'message' => 'Event berhasil diperbarui.',
            'event'   => [
                'id'               => 'custom-' . $event->id,
                'raw_id'           => $event->id,
                'source'           => 'custom',
                'title'            => $event->title,
                'description'      => $event->description ?? '',
                'startTime'        => $event->start_time->toISOString(),
                'endTime'          => $event->end_time->toISOString(),
                'start'            => $event->start_time->toISOString(),
                'end'              => $event->end_time->toISOString(),
                'color'            => $event->color,
                'category'         => $event->category,
                'tags'             => $event->tags ?? [],
                'google_meet_link' => $event->google_meet_link,
                'creator'          => [
                    'id'   => $event->creator?->id,
                    'name' => $event->creator?->name,
                ],
                'attendees'        => $event->attendees->map(fn($a) => [
                    'id'     => $a->id,
                    'name'   => $a->name,
                    'email'  => $a->email,
                    'avatar' => $a->avatar,
                ])->values()->all(),
                'attendee_ids'     => $event->attendees->pluck('id')->values()->all(),
                'can_edit'         => true,
            ],
        ]);
    }

    public function destroy($id)
    {
        $event = CalendarEvent::findOrFail($id);
        $user  = auth()->user();

        if (! $user->is_super_admin && $event->creator_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus event ini.');
        }

        $event->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Event berhasil dihapus.',
        ]);
    }

    public function upcoming(Request $request)
    {
        $days   = (int) $request->input('days', 7);
        $start  = now()->startOfDay();
        $end    = now()->addDays($days)->endOfDay();
        $user   = auth()->user();
        $isCustomer = $user->hasRole('client');

        $companyScope = function ($q) use ($user, $isCustomer) {
            if (! $user->is_super_admin && $user->company_id) {
                $q->where('company_id', $user->company_id);
            }
            if ($isCustomer) {
                $q->where('client_id', $user->id);
            }
        };

        $items = collect();

        // Custom events
        CalendarEvent::where(function ($q) use ($start, $end) {
            $q->whereBetween('start_time', [$start, $end]);
        })->where(function ($q) use ($user) {
            if (! $user->is_super_admin) {
                $q->where('creator_id', $user->id)
                  ->orWhereHas('attendees', fn($att) => $att->where('user_id', $user->id));
            }
        })->get()->each(fn($e) => $items->push([
            'type'             => 'custom',
            'title'            => $e->title,
            'date'             => $e->start_time->toDateString(),
            'time'             => $e->start_time->format('H:i'),
            'category'         => $e->category,
            'google_meet_link' => $e->google_meet_link,
            'url'              => null,
        ]));

        // Upcoming tasks
        Task::with(['project'])->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->whereNotIn('status', ['done'])->whereNull('deleted_at')
            ->whereHas('project', $companyScope)
            ->get()->each(fn($t) => $items->push([
                'type'     => 'task',
                'title'    => $t->title,
                'date'     => $t->due_date->toDateString(),
                'project'  => $t->project?->name,
                'priority' => $t->priority,
                'url'      => $t->project ? route('tasks.show', [$t->project, $t->id]) : route('tasks.show', [$t->project_id, $t->id]),
            ]));

        // Upcoming milestones
        Milestone::with(['project'])
            ->whereBetween('due_date', [$start, $end])
            ->where('status', '!=', 'completed')
            ->whereHas('project', $companyScope)
            ->get()->each(fn($m) => $items->push([
                'type'    => 'milestone',
                'title'   => $m->title,
                'date'    => $m->due_date?->toDateString(),
                'project' => $m->project?->name,
                'url'     => $m->project ? route('projects.show', $m->project) : ($m->project_id ? route('projects.show', $m->project_id) : '#'),
            ]));

        return response()->json($items->sortBy('date')->values());
    }
}
