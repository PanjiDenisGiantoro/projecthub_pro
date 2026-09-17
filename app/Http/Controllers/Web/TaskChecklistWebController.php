<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskChecklistItem;
use Illuminate\Http\Request;

class TaskChecklistWebController extends Controller
{
    // --- Checklist Groups ---

    public function storeGroup(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $nextOrder = $task->checklists()->max('sort_order') + 1;

        $checklist = $task->checklists()->create([
            'title'      => $data['title'],
            'sort_order' => $nextOrder,
        ]);

        return response()->json($checklist->load('items'), 201);
    }

    public function updateGroup(Request $request, Project $project, Task $task, TaskChecklist $checklist)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id || $checklist->task_id !== $task->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $checklist->update($data);

        return response()->json($checklist);
    }

    public function destroyGroup(Project $project, Task $task, TaskChecklist $checklist)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id || $checklist->task_id !== $task->id, 404);

        $checklist->delete();

        return response()->json(['ok' => true]);
    }

    // --- Checklist Items ---

    public function storeItem(Request $request, Project $project, Task $task, TaskChecklist $checklist)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id || $checklist->task_id !== $task->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:500',
        ]);

        $nextOrder = $checklist->items()->max('sort_order') + 1;

        $item = $checklist->items()->create([
            'title'      => $data['title'],
            'is_done'    => false,
            'sort_order' => $nextOrder,
        ]);

        return response()->json($item, 201);
    }

    public function updateItem(Request $request, Project $project, Task $task, TaskChecklist $checklist, TaskChecklistItem $item)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id || $checklist->task_id !== $task->id || $item->checklist_id !== $checklist->id, 404);

        $data = $request->validate([
            'title'   => 'sometimes|required|string|max:500',
            'is_done' => 'sometimes|boolean',
        ]);

        // Track completion timestamp
        if (isset($data['is_done'])) {
            if ($data['is_done'] && ! $item->is_done) {
                $data['completed_at'] = now();
                $data['completed_by'] = auth()->id();
            } elseif (! $data['is_done']) {
                $data['completed_at'] = null;
                $data['completed_by'] = null;
            }
        }

        $item->update($data);

        return response()->json($item);
    }

    public function destroyItem(Project $project, Task $task, TaskChecklist $checklist, TaskChecklistItem $item)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id || $checklist->task_id !== $task->id || $item->checklist_id !== $checklist->id, 404);

        $item->delete();

        return response()->json(['ok' => true]);
    }
}
