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
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_done' => 'boolean',
        ]);

        $slug = Str::slug($data['name'], '_');

        if ($project->boardColumns()->where('slug', $slug)->exists()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => "Kolom dengan nama \"{$data['name']}\" sudah ada."], 422);
            }
            return back()->with('error', "Kolom dengan nama \"{$data['name']}\" sudah ada di proyek ini.");
        }

        $nextOrder = (int) $project->boardColumns()->max('sort_order') + 1;

        $color = $data['color'] ?? 'blue';
        if (!in_array($color, BoardColumnPalette::keys())) {
            $color = 'blue';
        }

        $column = $project->boardColumns()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $color,
            'icon' => $data['icon'] ?? 'clipboard',
            'is_done' => $request->boolean('is_done'),
            'sort_order' => $nextOrder,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'column' => $column]);
        }

        return back()->with('success', 'Kolom berhasil ditambahkan.');
    }

    public function update(Request $request, Project $project, BoardColumn $column)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_done' => 'boolean',
        ]);

        $color = $data['color'] ?? $column->color;
        if (!in_array($color, BoardColumnPalette::keys())) {
            $color = $column->color;
        }

        $column->update([
            'name' => $data['name'],
            'color' => $color,
            'icon' => $data['icon'] ?? $column->icon,
            'is_done' => $request->boolean('is_done', $column->is_done),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'column' => $column]);
        }

        return back()->with('success', 'Kolom berhasil diperbarui.');
    }

    public function destroy(Request $request, Project $project, BoardColumn $column)
    {
        // Support moving existing tasks to target bucket or delete all tasks
        $moveToId = $request->input('move_to_column_id');
        $deleteTasks = $request->boolean('delete_tasks');

        if ($moveToId) {
            $targetCol = $project->boardColumns()->find($moveToId);
            if ($targetCol) {
                $column->tasks()->update([
                    'board_column_id' => $targetCol->id,
                    'status' => $targetCol->slug,
                ]);
            }
        } elseif ($deleteTasks) {
            $column->tasks()->delete();
        } elseif ($column->tasks()->exists()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Kolom masih memiliki task, pindahkan dulu sebelum menghapus.'], 422);
            }
            return back()->with('error', 'Kolom masih memiliki task, pindahkan dulu sebelum menghapus.');
        }

        $column->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Kolom berhasil dihapus.');
    }

    /**
     * Instantly bootstrap standard agile default buckets: To Do, In Progress, Review, Completed.
     */
    public function bootstrapDefault(Request $request, Project $project)
    {
        $defaults = [
            ['name' => 'To Do', 'slug' => 'to_do', 'color' => 'slate', 'icon' => 'clipboard', 'is_done' => false, 'sort_order' => 1],
            ['name' => 'In Progress', 'slug' => 'in_progress', 'color' => 'blue', 'icon' => 'flame', 'is_done' => false, 'sort_order' => 2],
            ['name' => 'Review', 'slug' => 'review', 'color' => 'purple', 'icon' => 'shield', 'is_done' => false, 'sort_order' => 3],
            ['name' => 'Completed', 'slug' => 'completed', 'color' => 'green', 'icon' => 'check', 'is_done' => true, 'sort_order' => 4],
        ];

        foreach ($defaults as $def) {
            if (!$project->boardColumns()->where('slug', $def['slug'])->exists()) {
                $project->boardColumns()->create($def);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'columns' => $project->boardColumns()->orderBy('sort_order')->get()
            ]);
        }

        return back()->with('success', 'Default agile buckets berhasil dibuat.');
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
