<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;

class ProjectStatusWebController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return response()->json($project->getProjectStatuses());
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'color' => 'nullable|string|max:50',
        ]);

        $name = trim($data['name']);
        $color = $data['color'] ?? 'blue';

        // Check if name matches default statuses (case-insensitive)
        $isDefault = collect(ProjectStatus::$defaultStatuses)
            ->contains(fn($d) => strtolower($d['name']) === strtolower($name));

        $status = $project->statuses()->firstOrCreate(
            ['name' => $name],
            [
                'color' => $color,
                'sort_order' => $project->statuses()->count() + 5,
            ]
        );

        return response()->json([
            'id' => $status->id,
            'name' => $status->name,
            'color' => $status->color,
            'is_default' => $isDefault,
        ], 201);
    }

    public function destroy(Project $project, ProjectStatus $status)
    {
        $this->authorize('edit project', $project);
        abort_if($status->project_id !== $project->id, 404);

        $status->delete();

        return response()->json(['ok' => true]);
    }
}

