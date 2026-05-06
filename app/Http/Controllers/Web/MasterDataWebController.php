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
        $stats = [
            'companies'         => Company::count(),
            'branches'          => Branch::count(),
            'divisions'         => Division::count(),
            'departments'       => Department::count(),
            'structural_levels' => StructuralLevel::count(),
        ];

        $companies = Company::with([
            'branches.divisions.departments.head',
            'branches.divisions.departments' => fn($q) => $q->withCount('users'),
        ])->get();

        return view('master.index', compact('stats', 'companies'));
    }
}
