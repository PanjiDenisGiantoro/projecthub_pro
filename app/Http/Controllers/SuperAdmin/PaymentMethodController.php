<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return view('superadmin.payment-methods', [
            'methods' => PaymentMethod::ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $method = PaymentMethod::create($this->validated($request));

        return back()->with('success', "Metode bayar {$method->name} berhasil ditambahkan.");
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update($this->validated($request, $paymentMethod));

        return back()->with('success', "Metode bayar {$paymentMethod->name} berhasil diperbarui.");
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return back()->with('success', "Metode bayar {$paymentMethod->name} " . ($paymentMethod->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return back()->with('success', "Metode bayar {$paymentMethod->name} berhasil dihapus.");
    }

    private function validated(Request $request, ?PaymentMethod $ignore = null): array
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9_]+$/', Rule::unique('payment_methods', 'code')->ignore($ignore)],
            'name'        => 'required|string|max:100',
            'group'       => ['required', Rule::in(PaymentMethod::GROUPS)],
            'fee_flat'    => 'required|integer|min:0',
            'fee_percent' => 'required|numeric|min:0|max:100',
            'sort_order'  => 'nullable|integer|min:0',
        ], [
            'code.regex' => 'Kode harus huruf besar/angka/underscore sesuai kode DOKU, mis. VIRTUAL_ACCOUNT_BCA.',
        ], [
            'code'        => 'kode DOKU',
            'name'        => 'nama',
            'group'       => 'kelompok',
            'fee_flat'    => 'biaya flat',
            'fee_percent' => 'biaya persen',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $request->boolean('is_active');

        return $data;
    }
}
