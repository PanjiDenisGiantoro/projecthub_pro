<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StructuralLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StructuralLevelWebController extends Controller
{
    public function index(Request $request)
    {
        $cid = $this->tenantId();

        $levels = StructuralLevel::withCount('users')
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->paginate(20);

        // Ada template default (company_id null) yang belum diisi untuk company ini
        $hasDefaults = $cid ? StructuralLevel::whereNull('company_id')->exists() : false;

        return view('master.structural_levels.index', compact('levels', 'hasDefaults'));
    }

    public function create()
    {
        $cid = $this->tenantId();
        $nextOrder = StructuralLevel::when($cid, fn($q) => $q->where('company_id', $cid))->max('sort_order') + 1;
        return view('master.structural_levels.create', compact('nextOrder'));
    }

    public function store(Request $request)
    {
        $cid = $this->tenantId();

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100', Rule::unique('structural_levels', 'name')->where(fn($q) => $q->where('company_id', $cid))],
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active');
        $data['company_id'] = $cid;

        StructuralLevel::create($data);

        return redirect()->route('structural-levels.index')->with('success', 'Level struktural berhasil ditambahkan.');
    }

    public function edit(StructuralLevel $structuralLevel)
    {
        $this->authorizeLevel($structuralLevel);

        return view('master.structural_levels.edit', compact('structuralLevel'));
    }

    public function update(Request $request, StructuralLevel $structuralLevel)
    {
        $this->authorizeLevel($structuralLevel);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100', Rule::unique('structural_levels', 'name')->ignore($structuralLevel->id)->where(fn($q) => $q->where('company_id', $structuralLevel->company_id))],
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $structuralLevel->update($data);

        return redirect()->route('structural-levels.index')->with('success', 'Level struktural berhasil diperbarui.');
    }

    public function destroy(StructuralLevel $structuralLevel)
    {
        $this->authorizeLevel($structuralLevel);

        if ($structuralLevel->users()->exists()) {
            return back()->with('danger', 'Tidak bisa menghapus level yang masih digunakan oleh user.');
        }

        $structuralLevel->delete();
        return redirect()->route('structural-levels.index')->with('success', 'Level struktural dihapus.');
    }

    /** Salin template default (company_id null) ke company tenant saat ini. */
    public function resetDefault()
    {
        $cid = $this->tenantId();

        if ($cid === null) {
            return back()->with('danger', 'Super admin tidak memiliki perusahaan untuk direset.');
        }

        StructuralLevel::whereNull('company_id')->get()->each(function ($level) use ($cid) {
            StructuralLevel::updateOrCreate(
                ['company_id' => $cid, 'name' => $level->name],
                ['sort_order' => $level->sort_order, 'is_active' => $level->is_active]
            );
        });

        return redirect()->route('structural-levels.index')->with('success', 'Level struktural direset ke default.');
    }

    /** Template (company_id null) hanya boleh diubah super admin; selain itu harus milik tenant sendiri. */
    private function authorizeLevel(StructuralLevel $structuralLevel): void
    {
        if ($structuralLevel->company_id === null) {
            if ($this->tenantId() !== null) {
                abort(403);
            }
            return;
        }

        $this->authorizeCompany($structuralLevel->company_id);
    }
}
