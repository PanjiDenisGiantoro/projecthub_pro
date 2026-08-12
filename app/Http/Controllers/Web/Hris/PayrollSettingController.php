<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Pph21Setting;
use Illuminate\Http\Request;

class PayrollSettingController extends Controller
{
    public function edit()
    {
        $this->authorize('update payroll');
        $setting = Pph21Setting::forCompany(auth()->user()->company_id);

        return view('hris.payroll.setting', compact('setting'));
    }

    public function update(Request $request)
    {
        $this->authorize('update payroll');
        $data = $request->validate([
            'method' => 'required|in:progresif,ter',
        ]);

        Pph21Setting::forCompany(auth()->user()->company_id)->update($data);

        return back()->with('success', 'Metode perhitungan PPh 21 berhasil diperbarui.');
    }
}
