<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\StructuralLevel;

class MasterDataWebController extends Controller
{
    public function index()
    {
        $cid = $this->tenantId();

        $stats = [
            'companies'         => Company::when($cid, fn($q) => $q->where('id', $cid))->count(),
            'branches'          => Branch::when($cid, fn($q) => $q->where('company_id', $cid))->count(),
            'divisions'         => Division::when($cid, fn($q) => $q->whereHas('branch', fn($b) => $b->where('company_id', $cid)))->count(),
            'departments'       => Department::when($cid, fn($q) => $q->whereHas('division.branch', fn($b) => $b->where('company_id', $cid)))->count(),
            'structural_levels' => StructuralLevel::when($cid, fn($q) => $q->where('company_id', $cid))->count(),
        ];

        $companies = Company::with([
            'branches.divisions.departments.head',
            'branches.divisions.departments' => fn($q) => $q->withCount('users'),
        ])
        ->when($cid, fn($q) => $q->where('id', $cid))
        ->get();

        return view('master.index', compact('stats', 'companies'));
    }
}
