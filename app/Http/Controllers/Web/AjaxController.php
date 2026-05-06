<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function companies()
    {
        return response()->json(
            Company::where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );
    }

    public function branches(Request $request)
    {
        $request->validate(['company_id' => 'required|integer']);

        return response()->json(
            Branch::where('company_id', $request->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function divisions(Request $request)
    {
        $request->validate(['branch_id' => 'required|integer']);

        return response()->json(
            Division::where('branch_id', $request->branch_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function departments(Request $request)
    {
        $request->validate(['division_id' => 'required|integer']);

        return response()->json(
            Department::where('division_id', $request->division_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }
}
