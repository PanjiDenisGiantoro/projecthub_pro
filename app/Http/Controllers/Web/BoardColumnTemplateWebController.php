<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BoardColumnTemplate;
use App\Models\Project;
use App\Support\BoardColumnPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoardColumnTemplateWebController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $templates = BoardColumnTemplate::with(['creator', 'items'])
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->latest()
            ->get();

        $categories = BoardColumnTemplate::CATEGORIES;

        return view('board-column-templates.index', compact('templates', 'categories'));
    }

    public function create()
    {
        $colors = BoardColumnPalette::keys();
        $categories = BoardColumnTemplate::CATEGORIES;

        return view('board-column-templates.create', compact('colors', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.color' => ['required', 'in:'.implode(',', BoardColumnPalette::keys())],
        ]);

        $template = BoardColumnTemplate::create([
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'created_by' => auth()->id(),
        ]);

        foreach ($request->input('items', []) as $i => $item) {
            $template->items()->create([
                'name' => $item['name'],
                'slug' => Str::slug($item['name'], '_'),
                'color' => $item['color'],
                'is_done' => ! empty($item['is_done']),
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('board-column-templates.show', $template)->with('success', 'Template kolom dibuat.');
    }

    public function show(BoardColumnTemplate $template)
    {
        // Template ini bukan model tenant-scoped kayak Project, jadi cek company manual di sini.
        // Global template (company_id null) boleh dilihat semua orang; punya company lain tidak.
        $user = auth()->user();
        abort_unless(
            $user->is_super_admin || $template->company_id === null || $template->company_id === $user->company_id,
            403
        );

        $template->load('items', 'creator');

        return view('board-column-templates.show', compact('template'));
    }

    public function destroy(BoardColumnTemplate $template)
    {
        $user = auth()->user();
        // Template global (company_id null) cuma boleh dihapus super admin — itu default sistem
        // yang dipakai buat auto-provision kolom board semua project baru di semua company.
        abort_unless(
            $user->is_super_admin || ($template->company_id !== null && $template->company_id === $user->company_id),
            403
        );

        $template->delete();

        return redirect()->route('board-column-templates.index')->with('success', 'Template kolom dihapus.');
    }

    public function applyForm(BoardColumnTemplate $template)
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('board-column-templates.apply', compact('template', 'projects'));
    }

    public function applyToProject(Request $request, BoardColumnTemplate $template)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($data['project_id']);

        if ($project->boardColumns()->exists()) {
            return back()->with('error', "Proyek \"{$project->name}\" sudah punya kolom board. Hapus kolom yang ada dulu sebelum menerapkan template lain.");
        }

        $template->load('items');
        $template->applyTo($project);

        return redirect()->route('projects.show', $project)->with('success', "Template kolom \"{$template->name}\" berhasil diterapkan ke proyek \"{$project->name}\".");
    }
}
