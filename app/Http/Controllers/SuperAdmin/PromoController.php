<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index()
    {
        return view('superadmin.promos', [
            'promos'    => Promo::with('company')->orderByDesc('start_date')->orderByDesc('id')->get(),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $promo = Promo::create($this->validated($request));

        return back()->with('success', "Promo {$promo->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Promo $promo)
    {
        $promo->update($this->validated($request));

        return back()->with('success', "Promo {$promo->name} berhasil diperbarui.");
    }

    public function toggle(Promo $promo)
    {
        $promo->update(['is_active' => ! $promo->is_active]);

        return back()->with('success', 'Status promo diperbarui.');
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();

        return back()->with('success', 'Promo berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'company_id'     => 'nullable|exists:companies,id',
            'discount_type'  => 'required|in:' . implode(',', array_keys(Promo::TYPES)),
            'discount_value' => ['required', 'numeric', 'min:0.01', $request->input('discount_type') === 'percent' ? 'max:100' : 'max:9999999999'],
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'notes'          => 'nullable|string|max:255',
        ], [], [
            'name'           => 'nama promo',
            'company_id'     => 'perusahaan',
            'discount_type'  => 'tipe potongan',
            'discount_value' => 'nilai potongan',
            'start_date'     => 'tanggal mulai',
            'end_date'       => 'tanggal berakhir',
        ]);

        $data['company_id'] = $data['company_id'] ?: null;
        $data['is_active']  = $request->boolean('is_active');

        return $data;
    }
}
