<div class="bg-white rounded-xl border border-gray-200">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <div>
            <h3 class="font-semibold text-gray-900">Nilai PTKP</h3>
            <p class="text-xs text-gray-500 mt-0.5">Penghasilan Tidak Kena Pajak per status — PMK-168/2023</p>
        </div>
        <form action="{{ route('hris.master.tax-ptkp.reset') }}" method="POST" onsubmit="return confirm('Reset ke nilai PMK-168/2023?')">
            @csrf
            <button class="text-xs text-amber-600 border border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-50">Reset Default</button>
        </form>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Nilai PTKP/Tahun</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($ptkpList as $ptkp)
            <tr x-data="{ edit: false }">
                <td class="px-4 py-3 font-mono font-semibold text-violet-700">{{ $ptkp->status_code }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $ptkp->label }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                    Rp {{ number_format($ptkp->amount, 0, ',', '.') }}
                </td>
                <td class="px-4 py-3 text-center">
                    <button @click="edit = !edit" class="text-xs text-violet-600 hover:underline">Edit</button>
                </td>
            </tr>
            <tr x-show="edit" x-cloak class="bg-blue-50">
                <td colspan="4" class="px-4 py-3">
                    <form action="{{ route('hris.master.tax-ptkp.update', $ptkp) }}" method="POST" class="flex items-center gap-3">
                        @csrf @method('PATCH')
                        <span class="font-mono font-bold text-violet-700 text-sm">{{ $ptkp->status_code }}</span>
                        <input type="number" name="amount" value="{{ $ptkp->amount }}" step="500000" min="0"
                               class="border border-gray-300 rounded-xl px-3 py-1.5 text-sm w-48 focus:ring-2 focus:ring-violet-500 focus:outline-none">
                        <button class="text-xs bg-violet-600 text-white px-3 py-1.5 rounded-lg hover:bg-violet-700">Simpan</button>
                        <button type="button" @click="edit = false" class="text-xs text-gray-500">Batal</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
