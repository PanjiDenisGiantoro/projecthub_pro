<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrganizationUnit;
use App\Models\StructuralLevel;

class MasterDataWebController extends Controller
{
    public function index()
    {
        $cid = $this->tenantId();

        $stats = [
            'companies'         => Company::when($cid, fn($q) => $q->where('id', $cid))->count(),
            'organization_units' => OrganizationUnit::when($cid, fn($q) => $q->where('company_id', $cid))->count(),
            'structural_levels' => StructuralLevel::when($cid, fn($q) => $q->where('company_id', $cid))->count(),
        ];

        $companies = Company::when($cid, fn($q) => $q->where('id', $cid))->get();

        $companies->each(function ($company) {
            $company->orgTree = OrganizationUnit::orderedTree($company->id)->load('head')->loadCount('users');
        });

        return view('master.index', compact('stats', 'companies'));
    }
}
