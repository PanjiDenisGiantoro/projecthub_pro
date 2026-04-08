<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Http\Request;

class ProjectWebController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Project::with(['client', 'manager'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"));

        if ($user->hasRole('customer')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole(['developer', 'marketing'])) {
            $query->whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->orWhere('manager_id', $user->id);
        }

        $projects = $query->latest()->paginate(12);
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $clients   = User::role('customer')->where('is_active', true)->get();
        $managers  = User::role('manager')->where('is_active', true)->get();
        return view('projects.create', compact('clients', 'managers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'client_id'  => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'budget'     => 'nullable|numeric|min:0',
        ]);

        $project = Project::create([
            ...$request->only('name', 'description', 'client_id', 'manager_id', 'start_date', 'end_date', 'budget'),
            'status' => 'draft',
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil dibuat.');
    }

    public function show(Project $project)
    {
        $project->load(['client', 'manager', 'members.user', 'milestones', 'tasks' => fn($q) => $q->with('assignee')->limit(10)]);
        $slaPolicies = app(SlaService::class);
        $developers  = User::role('developer')->where('is_active', true)->get();
        $recentTickets = $project->tickets()->with('reporter')->latest()->limit(5)->get();
        return view('projects.show', compact('project', 'developers', 'recentTickets'));
    }

    public function edit(Project $project)
    {
        $clients  = User::role('customer')->where('is_active', true)->get();
        $managers = User::role('manager')->where('is_active', true)->get();
        return view('projects.edit', compact('project', 'clients', 'managers'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'status'   => 'in:draft,active,on_hold,completed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $project->update($request->only('name', 'description', 'client_id', 'manager_id', 'status', 'start_date', 'end_date', 'budget', 'progress'));

        return redirect()->route('projects.show', $project)->with('success', 'Proyek diperbarui.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Proyek dihapus.');
    }

    public function addMember(Request $request, Project $project)
    {
        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'role'              => 'required|in:developer,marketing',
            'max_hours_per_day' => 'integer|min:1|max:24',
        ]);

        ProjectMember::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $request->user_id],
            ['role' => $request->role, 'max_hours_per_day' => $request->get('max_hours_per_day', 8)]
        );

        return back()->with('success', 'Anggota tim ditambahkan.');
    }

    public function removeMember(Project $project, User $user)
    {
        ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->delete();
        return back()->with('success', 'Anggota dihapus.');
    }

    public function timesheet(Request $request, Project $project)
    {
        $logs = TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->when($request->from, fn($q) => $q->whereDate('started_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('started_at', '<=', $request->to))
            ->get();

        $summary = $logs->groupBy('user_id')->map(fn($ul) => [
            'user'          => $ul->first()->user,
            'total_hours'   => round($ul->sum('minutes') / 60, 2),
            'logs_count'    => $ul->count(),
        ])->values();

        return view('projects.timesheet', compact('project', 'logs', 'summary'));
    }
}
