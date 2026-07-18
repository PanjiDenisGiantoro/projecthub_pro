<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrganizationUnit;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function companies()
    {
        return response()->json(
            Company::where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );
    }

    /** Anak langsung dari sebuah unit organisasi (atau root-level unit se-company jika parent_id kosong). */
    public function organizationUnits(Request $request)
    {
        $request->validate([
            'company_id' => 'required_without:parent_id|nullable|integer',
            'parent_id'  => 'nullable|integer',
        ]);

        $query = OrganizationUnit::where('is_active', true);

        if ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->where('company_id', $request->company_id)->whereNull('parent_id');
        }

        return response()->json($query->orderBy('order')->get(['id', 'name', 'code']));
    }
}
