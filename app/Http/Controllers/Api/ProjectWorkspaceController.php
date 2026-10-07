<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetEntry;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMember;
use App\Models\Risk;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Sprint, file, budget, risiko, anggota tim, dan komentar task untuk aplikasi
 * mobile. Validasi & aturan disalin dari controller web masing-masing
 * (SprintWebController, ProjectFileWebController, BudgetWebController,
 * RiskWebController, ProjectWebController, TaskWebController). Akses proyek
 * dicek dengan ProjectPolicy@view seperti middleware "can:view,project" di web.
 */
class ProjectWorkspaceController extends Controller
{
    private const SPRINT_STATUS_MAP = [
        'planning'    => 'planned',
        'planned'     => 'planned',
        'not_started' => 'planned',
        'active'      => 'active',
        'in_progress' => 'active',
        'at_risk'     => 'active',
        'completed'   => 'completed',
    ];

    public function __construct(private NotificationService $notifier) {}

    // ── Sprint ───────────────────────────────────────────────────────────────

    public function sprints(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $sprints = $project->sprints()->with('milestone:id,title')->withCount('tasks')->orderByDesc('start_date')->get();

        return response()->json($sprints->map(fn (Sprint $s) => $this->sprintJson($s))->values());
    }

    public function storeSprint(Request $request, Project $project, GoogleCalendarService $calendar): JsonResponse
    {
        $this->authorize('view', $project);
        $data = $request->validate($this->sprintRules(false));

        $data['project_id'] = $project->id;
        $data['created_by'] = $request->user()->id;
        $data['status']     = self::SPRINT_STATUS_MAP[$data['status'] ?? 'planned'] ?? 'planned';
        if (empty($data['priority'])) {
            $data['priority'] = 'normal';
        }

        $sprint = Sprint::create($data);

        if ($project->meeting_auto_create) {
            try {
                $calendar->createMeetingForSprint($sprint, $request->user());
            } catch (\Throwable $e) {
                // sama dengan web: gagal buat meeting tidak membatalkan sprint
            }
        }

        return response()->json($this->sprintJson($sprint->load('milestone:id,title')->loadCount('tasks')), 201);
    }

    public function updateSprint(Request $request, Project $project, Sprint $sprint, GoogleCalendarService $calendar): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($sprint->project_id === $project->id, 404);
        $data = $request->validate($this->sprintRules(true));
        $data['status'] = self::SPRINT_STATUS_MAP[$data['status']] ?? 'planned';

        // Hanya satu sprint aktif per proyek.
        if ($data['status'] === 'active') {
            $project->sprints()->where('id', '!=', $sprint->id)->whereIn('status', ['active', 'in_progress'])->update(['status' => 'completed']);
        }

        $sprint->update($data);

        if ($sprint->wasChanged('start_date') && $sprint->google_event_id) {
            try {
                $calendar->syncMeetingTime($sprint, $request->user());
            } catch (\Throwable $e) {
            }
        }

        return response()->json($this->sprintJson($sprint->fresh()->load('milestone:id,title')->loadCount('tasks')));
    }

    public function destroySprint(Project $project, Sprint $sprint): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($sprint->project_id === $project->id, 404);
        $sprint->tasks()->update(['sprint_id' => null]);
        $sprint->delete();

        return response()->json(['message' => 'Sprint berhasil dihapus.']);
    }

    private function sprintRules(bool $statusRequired): array
    {
        return [
            'name'         => 'required|string|max:255',
            'code'         => 'nullable|string|max:50',
            'goal'         => 'nullable|string',
            'milestone_id' => 'nullable|exists:milestones,id',
            'assigned_to'  => 'nullable|exists:users,id',
            'priority'     => 'nullable|in:normal,low,high,urgent',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'status'       => ($statusRequired ? 'required' : 'nullable') . '|in:not_started,planning,planned,active,in_progress,completed,at_risk',
        ];
    }

    private function sprintJson(Sprint $s): array
    {
        return [
            'id'           => $s->id,
            'name'         => $s->name,
            'code'         => $s->code,
            'goal'         => $s->goal,
            'status'       => $s->status,
            'priority'     => $s->priority,
            'start_date'   => $s->start_date ? substr((string) $s->start_date, 0, 10) : null,
            'end_date'     => $s->end_date ? substr((string) $s->end_date, 0, 10) : null,
            'milestone_id' => $s->milestone_id,
            'milestone'    => $s->milestone?->title,
            'assigned_to'  => $s->assigned_to,
            'tasks_count'  => (int) ($s->tasks_count ?? 0),
        ];
    }

    // ── File proyek ──────────────────────────────────────────────────────────

    public function files(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $files = $project->files()->with('uploader:id,name')->orderBy('folder')->orderByDesc('created_at')->get();

        return response()->json($files->map(fn (ProjectFile $f) => $this->fileJson($f))->values());
    }

    public function storeFile(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $request->validate([
            'files'       => 'required|array|min:1',
            'files.*'     => 'required|file|max:51200',
            'folder'      => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $folder  = $this->normalizeFolderPath($request->input('folder', 'General')) ?: 'General';
        $created = [];

        foreach ($request->file('files', []) as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $stored = $file->store("project-files/{$project->id}", 'public');
            $created[] = ProjectFile::create([
                'project_id'    => $project->id,
                'folder'        => $folder,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $stored,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'description'   => $request->input('description'),
                'uploaded_by'   => $request->user()->id,
            ]);
        }

        return response()->json(collect($created)->map(fn ($f) => $this->fileJson($f->load('uploader:id,name')))->values(), 201);
    }

    public function destroyFile(Project $project, ProjectFile $projectFile): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($projectFile->project_id === $project->id, 404);
        Storage::disk('public')->delete($projectFile->stored_name);
        $projectFile->delete();

        return response()->json(['message' => 'File dihapus.']);
    }

    private function normalizeFolderPath(?string $path): string
    {
        $segments = array_filter(array_map('trim', explode('/', $path ?? '')), fn ($s) => $s !== '');

        return implode('/', $segments);
    }

    private function fileJson(ProjectFile $f): array
    {
        return [
            'id'          => $f->id,
            'folder'      => $f->folder,
            'name'        => $f->original_name,
            'mime_type'   => $f->mime_type,
            'size'        => (int) $f->size,
            'description' => $f->description,
            'url'         => asset('storage/' . $f->stored_name),
            'uploaded_by' => $f->uploader?->name,
            'created_at'  => $f->created_at?->toIso8601String(),
        ];
    }

    // ── Budget ───────────────────────────────────────────────────────────────

    public function budget(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $entries = $project->budgetEntries()->orderByDesc('entry_date')->orderByDesc('id')->get();

        return response()->json([
            'summary' => [
                'budget'    => (float) $project->budget,
                'expenses'  => $project->totalExpenses(),
                'income'    => $project->totalIncome(),
                'balance'   => (float) $project->budget - $project->totalExpenses() + $project->totalIncome(),
                'percent'   => $project->budgetUsedPercent(),
                'threshold' => $project->budget_alert_threshold !== null ? (float) $project->budget_alert_threshold : null,
            ],
            'entries' => $entries->map(fn (BudgetEntry $e) => [
                'id'          => $e->id,
                'type'        => $e->type,
                'category'    => $e->category,
                'description' => $e->description,
                'amount'      => (float) $e->amount,
                'entry_date'  => $e->entry_date ? substr((string) $e->entry_date, 0, 10) : null,
                'reference'   => $e->reference,
            ])->values(),
        ]);
    }

    public function storeBudget(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $data = $request->validate([
            'type'        => 'required|in:income,expense',
            'category'    => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'amount'      => 'required|numeric|min:0',
            'entry_date'  => 'required|date',
            'reference'   => 'nullable|string|max:100',
        ]);
        BudgetEntry::create([...$data, 'project_id' => $project->id, 'created_by' => $request->user()->id]);

        return $this->budget($project);
    }

    public function destroyBudget(Project $project, BudgetEntry $budgetEntry): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($budgetEntry->project_id === $project->id, 404);
        $budgetEntry->delete();

        return $this->budget($project);
    }

    public function budgetThreshold(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $data = $request->validate(['budget_alert_threshold' => 'nullable|numeric|min:0|max:100']);
        $project->update(['budget_alert_threshold' => $data['budget_alert_threshold']]);

        return $this->budget($project->fresh());
    }

    // ── Risiko ───────────────────────────────────────────────────────────────

    public function risks(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json($project->risks()->orderByDesc('id')->get()->map(fn (Risk $r) => $this->riskJson($r))->values());
    }

    public function storeRisk(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $risk = Risk::create([...$request->validate($this->riskRules()), 'project_id' => $project->id, 'created_by' => $request->user()->id]);

        return response()->json($this->riskJson($risk), 201);
    }

    public function updateRisk(Request $request, Project $project, Risk $risk): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($risk->project_id === $project->id, 404);
        $risk->update($request->validate($this->riskRules()));

        return response()->json($this->riskJson($risk->fresh()));
    }

    public function destroyRisk(Project $project, Risk $risk): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($risk->project_id === $project->id, 404);
        $risk->delete();

        return response()->json(['message' => 'Risiko dihapus.']);
    }

    private function riskRules(): array
    {
        return [
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'category'        => 'required|in:technical,schedule,resource,budget,external,other',
            'probability'     => 'required|integer|min:1|max:5',
            'impact'          => 'required|integer|min:1|max:5',
            'status'          => 'required|in:open,mitigated,accepted,closed',
            'mitigation_plan' => 'nullable|string',
            'owner'           => 'nullable|string|max:100',
        ];
    }

    private function riskJson(Risk $r): array
    {
        return $r->only(['id', 'title', 'description', 'category', 'probability', 'impact', 'status', 'mitigation_plan', 'owner']);
    }

    // ── Anggota tim ──────────────────────────────────────────────────────────

    /** Sama dengan tombol "Tambah anggota" di web: bisa banyak user sekaligus, peran bebas. */
    public function addMembers(Request $request, Project $project): JsonResponse
    {
        $this->authorize('manage project members');
        $request->validate([
            'user_id'           => 'required|array|min:1',
            'user_id.*'         => 'exists:users,id',
            'role'              => 'nullable|string|max:100',
            'max_hours_per_day' => 'nullable|integer|min:1|max:24',
        ]);

        foreach ($request->user_id as $userId) {
            $member = ProjectMember::firstOrCreate(['project_id' => $project->id, 'user_id' => $userId]);
            $member->update(array_filter([
                'role'              => $request->input('role'),
                'max_hours_per_day' => $request->input('max_hours_per_day'),
            ], fn ($v) => $v !== null));

            if ((int) $userId !== $request->user()->id) {
                $this->notifier->send(
                    $userId,
                    'project_member_added',
                    'Ditambahkan ke Tim Proyek',
                    "{$request->user()->name} menambahkan Anda ke tim proyek \"{$project->name}\".",
                    ['project_id' => $project->id]
                );
            }
        }

        return response()->json($project->members()->with('user:id,name')->get()->map(fn ($m) => [
            'id'                => $m->id,
            'user_id'           => $m->user_id,
            'user_name'         => $m->user?->name,
            'role'              => $m->role,
            'max_hours_per_day' => $m->max_hours_per_day,
        ])->values());
    }

    public function removeMember(Project $project, User $user): JsonResponse
    {
        $this->authorize('manage project members');
        ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Anggota dihapus.']);
    }

    // ── Komentar task ────────────────────────────────────────────────────────

    public function taskComments(Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($task->project_id === $project->id, 404);

        return response()->json($task->comments()->with(['user:id,name', 'attachments'])->oldest()->get()->map(fn ($c) => $this->commentJson($c))->values());
    }

    public function storeTaskComment(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($task->project_id === $project->id, 404);
        $request->validate([
            'body'          => 'required|string|max:2000',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240',
        ]);

        $comment = $task->comments()->create(['user_id' => $request->user()->id, 'body' => $request->body]);

        foreach ($request->file('attachments', []) as $file) {
            $comment->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $file->store("task-comment-attachments/{$comment->id}", 'public'),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        collect([$task->assigned_to, $task->created_by])
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === $request->user()->id)
            ->each(fn ($userId) => $this->notifier->send($userId, 'task_comment_added', 'Komentar Baru',
                "{$request->user()->name} berkomentar di task \"{$task->title}\".", ['task_id' => $task->id]));

        return response()->json($this->commentJson($comment->load(['user:id,name', 'attachments'])), 201);
    }

    private function commentJson($c): array
    {
        return [
            'id'          => $c->id,
            'user_id'     => $c->user_id,
            'user_name'   => $c->user?->name,
            'body'        => $c->body,
            'created_at'  => $c->created_at?->toIso8601String(),
            'attachments' => $c->attachments->map(fn ($a) => [
                'name' => $a->file_name,
                'url'  => asset('storage/' . $a->file_path),
            ])->values(),
        ];
    }
}
