<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class SprintWebController extends Controller
{
    use HasPerPage;

    public function index(Project $project)
    {
        $sprints = $project->sprints()->with(['tasks.assignee'])->orderByDesc('start_date')->get();
        $backlog = $project->tasks()->whereNull('sprint_id')->with('assignee', 'milestone')->orderBy('sort_order')->get();
        return view('sprints.index', compact('project', 'sprints', 'backlog'));
    }

    public function allSprints(Request $request)
    {
        $authUser  = auth()->user();
        $companyId = $authUser->company_id;

        $companyScope = function ($q) use ($authUser, $companyId) {
            if (! $authUser->is_super_admin && $companyId) {
                $q->whereHas('project', fn($p) => $p->where('company_id', $companyId));
            }
        };

        $query = Sprint::with(['project'])
            ->tap($companyScope)
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        if ($authUser->hasRole('client')) {
            $query->whereHas('project', fn($p) => $p->where('client_id', $authUser->id));
        }

        $sprints = $query->orderByDesc('start_date')->paginate($this->perPage($request))->withQueryString();

        return view('sprints.all', compact('sprints'));
    }

    public function store(Request $request, Project $project, GoogleCalendarService $calendar)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        $sprint = Sprint::create($data);

        if ($project->meeting_auto_create) {
            try {
                $calendar->createMeetingForSprint($sprint, auth()->user());
            } catch (\Throwable $e) {
                // silent — user tetap bisa klik "Buat Meeting" manual nanti
            }
        }

        return back()->with('success', 'Sprint dibuat.');
    }

    public function show(Project $project, Sprint $sprint)
    {
        $sprint->load(['tasks' => fn($q) => $q->with('assignee', 'milestone')->orderBy('sort_order')]);
        $statuses = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
        return view('sprints.show', compact('project', 'sprint', 'statuses'));
    }

    public function update(Request $request, Project $project, Sprint $sprint, GoogleCalendarService $calendar)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'status'     => 'required|in:planned,active,completed',
        ]);

        // Only one sprint can be active at a time
        if ($data['status'] === 'active') {
            $project->sprints()->where('id', '!=', $sprint->id)->where('status', 'active')->update(['status' => 'completed']);
        }

        $sprint->update($data);

        if ($sprint->wasChanged('start_date') && $sprint->google_event_id) {
            try {
                $calendar->syncMeetingTime($sprint, auth()->user());
            } catch (\Throwable $e) {
                // silent — jadwal Google Calendar tetap yang lama, tidak blokir update sprint
            }
        }

        return back()->with('success', 'Sprint diperbarui.');
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        // Move tasks back to backlog
        $sprint->tasks()->update(['sprint_id' => null]);
        $sprint->delete();
        return back()->with('success', 'Sprint dihapus.');
    }

    public function addTask(Request $request, Project $project, Sprint $sprint)
    {
        $request->validate(['task_id' => 'required|exists:tasks,id']);
        Task::where('id', $request->task_id)->where('project_id', $project->id)->update(['sprint_id' => $sprint->id]);
        return back()->with('success', 'Task ditambahkan ke sprint.');
    }

    public function removeTask(Request $request, Project $project, Sprint $sprint)
    {
        $request->validate(['task_id' => 'required|exists:tasks,id']);
        Task::where('id', $request->task_id)->where('sprint_id', $sprint->id)->update(['sprint_id' => null]);
        return back()->with('success', 'Task dipindahkan ke backlog.');
    }

    public function createMeeting(Project $project, Sprint $sprint, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createMeetingForSprint($sprint, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat meeting. Silakan coba lagi.');
        }

        return back()->with('success', 'Meeting berhasil dibuat.');
    }

    public function createStandup(Project $project, Sprint $sprint, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createRecurringMeetingForSprint($sprint, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat standup harian. Silakan coba lagi.');
        }

        return back()->with('success', 'Standup harian berhasil dijadwalkan.');
    }
}
