<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Pph21Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'method'                 => 'required|in:progresif,ter',
            'payment_scheme'         => 'required|in:gross,gross_up,net',
            'jkk_rate'               => ['required', 'numeric', Rule::in(array_values(Pph21Setting::JKK_RATES))],
            'potongan_alpha_metode'  => 'required|in:proporsional,nominal',
            'potongan_alpha_nominal' => 'required_if:potongan_alpha_metode,nominal|nullable|numeric|min:0',
        ]);
        $data['tax_tunjangan_jabatan']   = $request->boolean('tax_tunjangan_jabatan');
        $data['tax_tunjangan_transport'] = $request->boolean('tax_tunjangan_transport');
        $data['tax_tunjangan_makan']     = $request->boolean('tax_tunjangan_makan');
        $data['potong_alpha']            = $request->boolean('potong_alpha');
        $data['potongan_alpha_nominal']  = $data['potongan_alpha_nominal'] ?? 0;

        Pph21Setting::forCompany(auth()->user()->company_id)->update($data);

        return back()->with('success', 'Metode perhitungan PPh 21 berhasil diperbarui.');
    }
}
