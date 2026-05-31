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
        $this->authorize('manage hris master');
        $tab       = $request->get('tab', 'leave-types');
        $companyId = auth()->user()->company_id;

        return view('hris.master.index', [
            'tab'           => $tab,
            'leaveTypes'    => LeaveType::where('company_id', $companyId)->orderBy('sort_order')->get(),
            'overtimeRules' => OvertimeRule::where('company_id', $companyId)->orderBy('day_type')->orderBy('hour_from')->get(),
            'ptkpList'      => TaxPtkp::orderBy('sort_order')->get(),
            'taxBrackets'   => TaxBracket::orderBy('sort_order')->get(),
        ]);
    }
}
