<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PpnRate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PpnRateController extends Controller
{
    public function index()
    {
        $rates = PpnRate::orderByDesc('start_date')->get();

        return view('superadmin.ppn-rates', [
            'rates'   => $rates,
            'current' => PpnRate::activeOn(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        PpnRate::create($data);

        return back()->with('success', 'Tarif PPN ' . PpnRate::formatRate($data['rate']) . ' berhasil ditambahkan.');
    }

    public function update(Request $request, PpnRate $ppnRate)
    {
        $data = $this->validated($request, $ppnRate);
        $ppnRate->update($data);

        return back()->with('success', 'Tarif PPN ' . PpnRate::formatRate($data['rate']) . ' berhasil diperbarui.');
    }

    public function destroy(PpnRate $ppnRate)
    {
        $ppnRate->delete();

        return back()->with('success', 'Tarif PPN berhasil dihapus.');
    }

    /** Validasi + tolak periode yang bertumpuk dengan periode lain (supaya tarif per tanggal selalu tunggal). */
    private function validated(Request $request, ?PpnRate $ignore = null): array
    {
        $data = $request->validate([
            'rate'       => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'notes'      => 'nullable|string|max:255',
        ], [], [
            'rate'       => 'tarif',
            'start_date' => 'tanggal mulai',
            'end_date'   => 'tanggal berakhir',
        ]);

        $start = $data['start_date'];
        $end   = $data['end_date'] ?? null;

        $overlap = PpnRate::when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $start))
            ->when($end, fn ($q) => $q->whereDate('start_date', '<=', $end))
            ->first();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Periode bertumpuk dengan tarif ' . PpnRate::formatRate($overlap->rate)
                    . ' (' . $overlap->start_date->format('d M Y') . ' – ' . ($overlap->end_date?->format('d M Y') ?? 'sekarang')
                    . '). Isi dulu tanggal berakhir periode tersebut.',
            ]);
        }

        return $data;
    }
}
