<div class="bg-white rounded-xl border border-gray-200" x-data="{ cat: 'A' }">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <div>
            <h3 class="font-semibold text-gray-900">Tarif Efektif Rata-rata (TER)</h3>
            <p class="text-xs text-gray-500 mt-0.5">PP 58/2023 & PMK 168/2023 — potongan bulanan Jan-Nov = bruto bulan berjalan × tarif TER. Desember tetap direkonsiliasi pakai tarif progresif.</p>
        </div>
        <form action="{{ route('hris.master.tax-ter.reset') }}" method="POST" onsubmit="return confirm('Reset ke tarif TER resmi PMK-168/2023? Semua perubahan manual akan tertimpa.')">
            @csrf
            <button class="text-xs text-amber-600 border border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-50 whitespace-nowrap">Reset Default</button>
        </form>
    </div>

    <div class="px-4 pt-3 text-xs text-gray-500 space-y-1">
        <p><span class="font-semibold text-blue-700">Kategori A</span> — status PTKP TK/0, TK/1, K/0</p>
        <p><span class="font-semibold text-blue-700">Kategori B</span> — status PTKP TK/2, TK/3, K/1, K/2</p>
        <p><span class="font-semibold text-blue-700">Kategori C</span> — status PTKP K/3</p>
    </div>

    {{-- Kategori tabs --}}
    <div class="flex gap-1 px-4 pt-3">
        @foreach(['A','B','C'] as $c)
        <button type="button" @click="cat = '{{ $c }}'"
                :class="cat === '{{ $c }}' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors">
            Kategori {{ $c }}
        </button>
        @endforeach
    </div>

    @foreach(['A','B','C'] as $c)
    @php $rows = $terRates->where('category', $c)->sortBy('sort_order'); @endphp
    <div x-show="cat === '{{ $c }}'" x-cloak class="p-4">
        <div class="max-h-[420px] overflow-y-auto border border-gray-100 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Bruto Dari</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Bruto Sampai</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Tarif</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $rate)
                    <tr class="{{ $rate->is_active ? '' : 'opacity-40' }}">
                        <td class="px-4 py-2 text-gray-700">Rp {{ number_format($rate->income_from, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-gray-700">
                            {{ $rate->income_to ? 'Rp ' . number_format($rate->income_to, 0, ',', '.') : '∞ Tak terbatas' }}
                        </td>
                        <td class="px-4 py-2 text-center font-semibold text-blue-700">{{ rtrim(rtrim(number_format($rate->rate * 100, 2, ',', '.'), '0'), ',') }}%</td>
                        <td class="px-4 py-2 text-center">
                            <form action="{{ route('hris.master.tax-ter.toggle', $rate) }}" method="POST">
                                @csrf @method('PATCH')
                                <button class="text-[11px] {{ $rate->is_active ? 'text-green-600' : 'text-gray-400' }} hover:underline">
                                    {{ $rate->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('hris.master.tax-ter.edit', $rate) }}" class="text-xs text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada tarif kategori {{ $c }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
