<?php

namespace App\Http\Controllers\Web\Hris\Master;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRule;
use Illuminate\Http\Request;

class OvertimeRuleController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('manage hris master');
        $request->validate([
            'day_type'   => 'required|in:weekday,weekend,holiday',
            'hour_from'  => 'required|integer|min:1',
            'hour_to'    => 'required|integer|min:0',
            'multiplier' => 'required|numeric|min:0.5',
            'label'      => 'nullable|string|max:100',
        ]);

        OvertimeRule::create([
            ...$request->only(['day_type','hour_from','hour_to','multiplier','label']),
            'company_id' => auth()->user()->company_id,
            'is_active'  => true,
        ]);

        return redirect()->route('hris.master.index', ['tab' => 'overtime-rules'])->with('success', 'Aturan lembur ditambahkan.');
    }

    public function update(Request $request, OvertimeRule $overtimeRule)
    {
        $this->authorize('manage hris master');
        $request->validate([
            'multiplier' => 'required|numeric|min:0.5',
            'label'      => 'nullable|string|max:100',
        ]);
        $overtimeRule->update($request->only(['day_type','hour_from','hour_to','multiplier','label']));
        return redirect()->route('hris.master.index', ['tab' => 'overtime-rules'])->with('success', 'Aturan lembur diperbarui.');
    }

    public function destroy(OvertimeRule $overtimeRule)
    {
        $this->authorize('manage hris master');
        $overtimeRule->delete();
        return redirect()->route('hris.master.index', ['tab' => 'overtime-rules'])->with('success', 'Aturan dihapus.');
    }

    public function toggle(OvertimeRule $overtimeRule)
    {
        $this->authorize('manage hris master');
        $overtimeRule->update(['is_active' => !$overtimeRule->is_active]);
        return back()->with('success', 'Status diperbarui.');
    }

    public function resetDefault()
    {
        $this->authorize('manage hris master');
        $companyId = auth()->user()->company_id;

        OvertimeRule::whereNull('company_id')->get()->each(function ($r) use ($companyId) {
            $data = collect($r->toArray())
                ->except(['id', 'company_id', 'created_at', 'updated_at'])
                ->toArray();

            OvertimeRule::updateOrCreate(
                ['company_id' => $companyId, 'day_type' => $r->day_type, 'hour_from' => $r->hour_from, 'hour_to' => $r->hour_to],
                $data
            );
        });

        return redirect()->route('hris.master.index', ['tab' => 'overtime-rules'])->with('success', 'Reset ke Permenaker No.5/2023 berhasil.');
    }
}
