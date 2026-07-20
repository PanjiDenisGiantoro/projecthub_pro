<?php

namespace App\Http\Controllers\Web\Hris\Master;

use App\Http\Controllers\Controller;
use App\Models\TaxBracket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaxBracketController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create hris master');
        $request->validate([
            'income_from' => 'required|numeric|min:0',
            'income_to'   => 'nullable|numeric|min:0',
            'rate'        => 'required|numeric|min:0|max:1',
            'label'       => 'nullable|string|max:100',
        ]);
        TaxBracket::create([...$request->only(['income_from','income_to','rate','label']), 'is_active' => true]);
        Cache::forget('tax_brackets_active');
        return redirect()->route('hris.master.index', ['tab' => 'tax-brackets'])->with('success', 'Lapisan tarif ditambahkan.');
    }

    public function edit(TaxBracket $taxBracket)
    {
        $this->authorize('view hris master');
        return view('hris.master.tax-brackets.edit', compact('taxBracket'));
    }

    public function update(Request $request, TaxBracket $taxBracket)
    {
        $this->authorize('update hris master');
        $request->validate([
            'income_from' => 'required|numeric|min:0',
            'income_to'   => 'nullable|numeric|min:0',
            'rate'        => 'required|numeric|min:0|max:1',
            'label'       => 'nullable|string|max:100',
        ]);
        $taxBracket->update($request->only(['income_from','income_to','rate','label']));
        Cache::forget('tax_brackets_active');
        return redirect()->route('hris.master.index', ['tab' => 'tax-brackets'])->with('success', 'Lapisan tarif diperbarui.');
    }

    public function destroy(TaxBracket $taxBracket)
    {
        $this->authorize('delete hris master');
        $taxBracket->delete();
        Cache::forget('tax_brackets_active');
        return redirect()->route('hris.master.index', ['tab' => 'tax-brackets'])->with('success', 'Lapisan tarif dihapus.');
    }

    public function toggle(TaxBracket $taxBracket)
    {
        $this->authorize('update hris master');
        $taxBracket->update(['is_active' => !$taxBracket->is_active]);
        Cache::forget('tax_brackets_active');
        return back()->with('success', 'Status diperbarui.');
    }

    public function resetDefault()
    {
        $this->authorize('create hris master');
        $brackets = [
            [0,              60_000_000,    0.05,  '5%  — s/d Rp 60 juta'],
            [60_000_000,     250_000_000,   0.15,  '15% — Rp 60 jt s/d Rp 250 jt'],
            [250_000_000,    500_000_000,   0.25,  '25% — Rp 250 jt s/d Rp 500 jt'],
            [500_000_000,    5_000_000_000, 0.30,  '30% — Rp 500 jt s/d Rp 5 M'],
            [5_000_000_000,  null,          0.35,  '35% — di atas Rp 5 M'],
        ];
        foreach ($brackets as $i => [$from, $to, $rate, $label]) {
            TaxBracket::updateOrCreate(['income_from' => $from], ['income_to' => $to, 'rate' => $rate, 'label' => $label, 'sort_order' => $i, 'is_active' => true]);
        }
        Cache::forget('tax_brackets_active');
        return redirect()->route('hris.master.index', ['tab' => 'tax-brackets'])->with('success', 'Reset ke UU HPP berhasil.');
    }
}
