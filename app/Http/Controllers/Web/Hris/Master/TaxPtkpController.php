<?php

namespace App\Http\Controllers\Web\Hris\Master;

use App\Http\Controllers\Controller;
use App\Models\TaxPtkp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaxPtkpController extends Controller
{
    public function update(Request $request, TaxPtkp $taxPtkp)
    {
        $this->authorize('update hris master');
        $request->validate(['amount' => 'required|numeric|min:0']);
        $taxPtkp->update(['amount' => $request->amount]);
        Cache::forget("tax_ptkp_{$taxPtkp->status_code}");
        return redirect()->route('hris.master.index', ['tab' => 'tax-ptkp'])->with('success', 'Nilai PTKP diperbarui.');
    }

    public function toggle(TaxPtkp $taxPtkp)
    {
        $this->authorize('update hris master');
        $taxPtkp->update(['is_active' => !$taxPtkp->is_active]);
        return back()->with('success', 'Status diperbarui.');
    }

    public function resetDefault()
    {
        $this->authorize('create hris master');
        $defaults = [
            ['TK/0', 'Tidak Kawin, 0 Tanggungan',            54_000_000],
            ['TK/1', 'Tidak Kawin, 1 Tanggungan',            58_500_000],
            ['TK/2', 'Tidak Kawin, 2 Tanggungan',            63_000_000],
            ['TK/3', 'Tidak Kawin, 3 Tanggungan',            67_500_000],
            ['K/0',  'Kawin, 0 Tanggungan',                  58_500_000],
            ['K/1',  'Kawin, 1 Tanggungan',                  63_000_000],
            ['K/2',  'Kawin, 2 Tanggungan',                  67_500_000],
            ['K/3',  'Kawin, 3 Tanggungan',                  72_000_000],
            ['K/I/0','Kawin, Istri Bekerja, 0 Tanggungan',  108_000_000],
            ['K/I/1','Kawin, Istri Bekerja, 1 Tanggungan',  112_500_000],
            ['K/I/2','Kawin, Istri Bekerja, 2 Tanggungan',  117_000_000],
            ['K/I/3','Kawin, Istri Bekerja, 3 Tanggungan',  121_500_000],
        ];
        foreach ($defaults as $i => [$code, $label, $amount]) {
            TaxPtkp::updateOrCreate(['status_code' => $code], ['label' => $label, 'amount' => $amount, 'sort_order' => $i, 'is_active' => true]);
            Cache::forget("tax_ptkp_{$code}");
        }
        return redirect()->route('hris.master.index', ['tab' => 'tax-ptkp'])->with('success', 'Reset ke PMK-168/2023 berhasil.');
    }
}
