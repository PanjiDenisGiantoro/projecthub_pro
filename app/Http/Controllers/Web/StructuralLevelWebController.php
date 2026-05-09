<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StructuralLevel;
use Illuminate\Http\Request;

class StructuralLevelWebController extends Controller
{
    public function index(Request $request)
    {
        $levels = StructuralLevel::withCount('users')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->paginate(20);

        return view('master.structural_levels.index', compact('levels'));
    }

    public function create()
    {
        $nextOrder = StructuralLevel::max('sort_order') + 1;
        return view('master.structural_levels.create', compact('nextOrder'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100|unique:structural_levels,name',
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        StructuralLevel::create($data);

        return redirect()->route('structural-levels.index')->with('success', 'Level struktural berhasil ditambahkan.');
    }

    public function edit(StructuralLevel $structuralLevel)
    {
        return view('master.structural_levels.edit', compact('structuralLevel'));
    }

    public function update(Request $request, StructuralLevel $structuralLevel)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100|unique:structural_levels,name,' . $structuralLevel->id,
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $structuralLevel->update($data);

        return redirect()->route('structural-levels.index')->with('success', 'Level struktural berhasil diperbarui.');
    }

    public function destroy(StructuralLevel $structuralLevel)
    {
        if ($structuralLevel->users()->exists()) {
            return back()->with('danger', 'Tidak bisa menghapus level yang masih digunakan oleh user.');
        }

        $structuralLevel->delete();
        return redirect()->route('structural-levels.index')->with('success', 'Level struktural dihapus.');
    }
}
