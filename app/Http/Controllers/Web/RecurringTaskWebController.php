<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\RecurringTaskDefinition;
use App\Models\User;
use App\Services\RecurringTaskGenerator;
use Illuminate\Http\Request;

class RecurringTaskWebController extends Controller
{
    use HasPerPage;

    public function index(Request $request, Project $project)
    {
        $definitions = $project->recurringTasks()->with('assignee', 'milestone')->withCount('tasks')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();
        $milestones = $project->milestones()->orderBy('title')->get(['id', 'title']);
        $users = User::where('company_id', $project->company_id)->orderBy('name')->get(['id', 'name']);
        return view('recurring.index', compact('project', 'definitions', 'milestones', 'users'));
    }

    public function allRecurring(Request $request)
    {
        $authUser  = auth()->user();
        $companyId = $authUser->company_id;

        $companyScope = function ($q) use ($authUser, $companyId) {
            if (! $authUser->is_super_admin && $companyId) {
                $q->whereHas('project', fn($p) => $p->where('company_id', $companyId));
            }
        };

        $query = RecurringTaskDefinition::with(['project', 'assignee'])->withCount('tasks')
            ->tap($companyScope)
            ->when($request->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false));

        if ($authUser->hasRole('client')) {
            $query->whereHas('project', fn($p) => $p->where('client_id', $authUser->id));
        }

        $definitions = $query->orderByDesc('id')->paginate($this->perPage($request))->withQueryString();

        return view('recurring.all', compact('definitions'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'assigned_to'     => 'nullable|exists:users,id',
            'milestone_id'    => 'nullable|exists:milestones,id',
            'frequency'       => 'required|in:daily,weekly,biweekly,monthly',
            'day_of_week'     => 'nullable|integer|min:0|max:6',
            'day_of_month'    => 'nullable|integer|min:1|max:28',
            'priority'        => 'required|in:low,medium,high,critical',
            'estimated_hours' => 'nullable|integer|min:0',
            'due_offset_days' => 'required|integer|min:0',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        RecurringTaskDefinition::create($data);

        return back()->with('success', 'Recurring task ditambahkan.');
    }

    public function update(Request $request, Project $project, RecurringTaskDefinition $recurringTask)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'assigned_to'     => 'nullable|exists:users,id',
            'frequency'       => 'required|in:daily,weekly,biweekly,monthly',
            'day_of_week'     => 'nullable|integer|min:0|max:6',
            'day_of_month'    => 'nullable|integer|min:1|max:28',
            'priority'        => 'required|in:low,medium,high,critical',
            'estimated_hours' => 'nullable|integer|min:0',
            'due_offset_days' => 'required|integer|min:0',
            'is_active'       => 'boolean',
        ]);

        $recurringTask->update($data);

        return back()->with('success', 'Recurring task diperbarui.');
    }

    public function destroy(Project $project, RecurringTaskDefinition $recurringTask)
    {
        $recurringTask->update(['is_active' => false]);
        $recurringTask->delete();
        return back()->with('success', 'Recurring task dihapus.');
    }

    /** Tombol "Generate Sekarang" — buat task dari definisi ini sekarang juga, tanpa nunggu jadwal harian. */
    public function generateNow(Project $project, RecurringTaskDefinition $recurringTask, RecurringTaskGenerator $generator)
    {
        $task = $generator->generateOne($recurringTask, force: true);

        if (! $task) {
            return back()->withErrors(['Task untuk definisi ini sudah pernah dibuat hari ini.']);
        }

        return redirect()->route('tasks.show', [$project, $task])->with('success', "Task \"{$task->title}\" berhasil dibuat.");
    }
}
