<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateMilestone;
use App\Models\ProjectTemplateTask;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProjectTemplateWebController extends Controller
{
    public function index()
    {
        $templates = ProjectTemplate::with(['creator', 'milestones.tasks'])->latest()->get();
        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'milestones' => 'nullable|array',
            'milestones.*.title' => 'required|string|max:255',
        ]);

        $template = ProjectTemplate::create([
            'name'        => $request->name,
            'description' => $request->description,
            'category'    => $request->category,
            'created_by'  => auth()->id(),
        ]);

        foreach ($request->input('milestones', []) as $i => $ms) {
            $milestone = $template->milestones()->create([
                'title'        => $ms['title'],
                'description'  => $ms['description'] ?? null,
                'offset_days'  => $ms['offset_days'] ?? 0,
                'duration_days'=> $ms['duration_days'] ?? 7,
                'sort_order'   => $i,
            ]);

            foreach ($ms['tasks'] ?? [] as $j => $t) {
                $milestone->tasks()->create([
                    'title'           => $t['title'],
                    'description'     => $t['description'] ?? null,
                    'priority'        => $t['priority'] ?? 'medium',
                    'estimated_hours' => $t['estimated_hours'] ?? null,
                    'story_points'    => $t['story_points'] ?? null,
                    'sort_order'      => $j,
                ]);
            }
        }

        return redirect()->route('templates.show', $template)->with('success', 'Template dibuat.');
    }

    public function show(ProjectTemplate $template)
    {
        $template->load('milestones.tasks', 'creator');
        return view('templates.show', compact('template'));
    }

    public function destroy(ProjectTemplate $template)
    {
        $template->delete();
        return redirect()->route('templates.index')->with('success', 'Template dihapus.');
    }

    public function applyForm(ProjectTemplate $template)
    {
        $projects = Project::orderBy('name')->get(['id', 'name', 'start_date']);
        return view('templates.apply', compact('template', 'projects'));
    }

    public function applyToProject(Request $request, ProjectTemplate $template)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'start_date' => 'required|date',
        ]);

        $project = Project::findOrFail($data['project_id']);
        $startDate = Carbon::parse($data['start_date']);

        $template->load('milestones.tasks');

        foreach ($template->milestones as $tmpl) {
            $msStart = $startDate->copy()->addDays($tmpl->offset_days);
            $msEnd   = $msStart->copy()->addDays($tmpl->duration_days - 1);

            $milestone = $project->milestones()->create([
                'title'       => $tmpl->title,
                'description' => $tmpl->description,
                'start_date'  => $msStart,
                'due_date'    => $msEnd,
                'status'      => 'pending',
            ]);

            foreach ($tmpl->tasks as $i => $ttask) {
                $project->tasks()->create([
                    'milestone_id'    => $milestone->id,
                    'title'           => $ttask->title,
                    'description'     => $ttask->description,
                    'priority'        => $ttask->priority,
                    'estimated_hours' => $ttask->estimated_hours,
                    'story_points'    => $ttask->story_points,
                    'sort_order'      => $ttask->sort_order,
                    'status'          => 'todo',
                    'start_date'      => $msStart,
                    'due_date'        => $msEnd,
                    'created_by'      => auth()->id(),
                ]);
            }
        }

        return redirect()->route('projects.show', $project)->with('success', "Template \"{$template->name}\" berhasil diterapkan ke proyek \"{$project->name}\".");
    }
}
