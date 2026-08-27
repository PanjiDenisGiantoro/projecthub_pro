<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Support\BoardColumnPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoardColumnWebController extends Controller
{
    public function index(Project $project)
    {
        $columns = $project->boardColumns()->withCount('tasks')->get();
        $colors = BoardColumnPalette::keys();

        return view('projects.board-columns', compact('project', 'columns', 'colors'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => ['required', 'in:'.implode(',', BoardColumnPalette::keys())],
            'is_done' => 'boolean',
        ]);

        $slug = Str::slug($data['name'], '_');

        if ($project->boardColumns()->where('slug', $slug)->exists()) {
            return back()->with('error', "Kolom dengan nama \"{$data['name']}\" sudah ada di proyek ini.");
        }

        $nextOrder = (int) $project->boardColumns()->max('sort_order') + 1;

        $project->boardColumns()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'],
            'is_done' => $request->boolean('is_done'),
            'sort_order' => $nextOrder,
        ]);

        return back()->with('success', 'Kolom berhasil ditambahkan.');
    }

    public function update(Request $request, Project $project, BoardColumn $column)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => ['required', 'in:'.implode(',', BoardColumnPalette::keys())],
            'is_done' => 'boolean',
        ]);

        $column->update([
            'name' => $data['name'],
            'color' => $data['color'],
            'is_done' => $request->boolean('is_done'),
        ]);

        return back()->with('success', 'Kolom berhasil diperbarui.');
    }

    public function destroy(Project $project, BoardColumn $column)
    {
        if ($column->tasks()->exists()) {
            return back()->with('error', 'Kolom masih memiliki task, pindahkan dulu sebelum menghapus.');
        }

        $column->delete();

        return back()->with('success', 'Kolom berhasil dihapus.');
    }

    public function reorder(Request $request, Project $project)
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:board_columns,id',
        ]);

        foreach ($data['order'] as $index => $columnId) {
            $project->boardColumns()->where('id', $columnId)->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }
}
