<?php

namespace App\Http\Controllers\Web\Hris\Master;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\OvertimeRule;
use App\Models\TaxBracket;
use App\Models\TaxPtkp;
use Illuminate\Http\Request;

class HrisMasterController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view hris master');
        $tab        = $request->get('tab', 'leave-types');
        $user       = auth()->user();
        $isSuperAdmin = $user->is_super_admin;
        $companyId  = $user->company_id;

        return view('hris.master.index', [
            'tab'           => $tab,
            'isSuperAdmin'  => $isSuperAdmin,
            'leaveTypes'    => LeaveType::when(!$isSuperAdmin, fn($q) => $q->where('company_id', $companyId))
                                    ->with('company')
                                    ->orderBy('sort_order')->get(),
            'overtimeRules' => OvertimeRule::when(!$isSuperAdmin, fn($q) => $q->where('company_id', $companyId))
                                    ->with('company')
                                    ->orderBy('day_type')->orderBy('hour_from')->get(),
            'ptkpList'      => TaxPtkp::orderBy('sort_order')->get(),
            'taxBrackets'   => TaxBracket::orderBy('sort_order')->get(),
        ]);
    }
}
