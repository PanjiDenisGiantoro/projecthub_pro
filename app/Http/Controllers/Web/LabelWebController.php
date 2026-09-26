<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Http\Request;

class LabelWebController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return response()->json(
            $project->labels()->orderBy('name')->get()
        );
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'color' => ['required', 'in:' . implode(',', array_keys(Label::$colors))],
        ]);

        $label = $project->labels()->firstOrCreate(
            ['name' => $data['name']],
            ['color' => $data['color']]
        );

        return response()->json($label, 201);
    }

    public function update(Request $request, Project $project, Label $label)
    {
        $this->authorize('edit project', $project);
        abort_if($label->project_id !== $project->id, 404);

        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:100',
            'color' => ['sometimes', 'required', 'in:' . implode(',', array_keys(Label::$colors))],
        ]);

        $label->update($data);

        return response()->json($label);
    }

    public function destroy(Project $project, Label $label)
    {
        $this->authorize('edit project', $project);
        abort_if($label->project_id !== $project->id, 404);

        $label->delete();

        return response()->json(['ok' => true]);
    }

    public function attachToTask(Request $request, Project $project, \App\Models\Task $task, Label $label)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);
        abort_if($label->project_id !== $project->id, 404);

        $task->labels()->syncWithoutDetaching([$label->id]);

        return response()->json(['ok' => true]);
    }

    public function detachFromTask(Project $project, \App\Models\Task $task, Label $label)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $task->labels()->detach($label->id);

        return response()->json(['ok' => true]);
    }
}
