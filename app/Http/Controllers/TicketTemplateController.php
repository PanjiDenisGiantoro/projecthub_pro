<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TicketTemplate;
use Illuminate\Http\Request;

class TicketTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = TicketTemplate::with('creator:id,name')
            ->where(fn($q) => $q->whereNull('project_id')
                ->orWhere('project_id', $request->project_id))
            ->latest()
            ->get();

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bug,issue,enhancement,security,performance',
            'priority' => 'required|in:critical,high,medium,low',
            'description_template' => 'required|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $template = TicketTemplate::create([
            ...$request->only('name', 'type', 'priority', 'description_template', 'project_id'),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($template->load('creator:id,name'), 201);
    }

    public function show(TicketTemplate $template)
    {
        return response()->json($template->load('creator:id,name'));
    }

    public function update(Request $request, TicketTemplate $template)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:bug,issue,enhancement,security,performance',
            'priority' => 'sometimes|in:critical,high,medium,low',
            'description_template' => 'sometimes|string',
        ]);

        $template->update($request->only('name', 'type', 'priority', 'description_template'));

        return response()->json($template);
    }

    public function destroy(TicketTemplate $template)
    {
        $template->delete();
        return response()->json(['message' => 'Template deleted.']);
    }
}
