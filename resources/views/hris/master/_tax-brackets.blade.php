<div class="bg-white rounded-xl border border-gray-200">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <div>
            <h3 class="font-semibold text-gray-900">Tarif Progresif PPh 21</h3>
            <p class="text-xs text-gray-500 mt-0.5">Pasal 17 UU HPP — PKP = Bruto - Biaya Jabatan (5%, max Rp 6 jt) - PTKP</p>
        </div>
        <form action="{{ route('hris.master.tax-brackets.reset') }}" method="POST" onsubmit="return confirm('Reset ke tarif UU HPP?')">
            @csrf
            <button class="text-xs text-amber-600 border border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-50">Reset Default</button>
        </form>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">PKP Dari</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">PKP Sampai</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Tarif</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($taxBrackets as $bracket)
            <tr>
                <td class="px-4 py-3 text-gray-700">Rp {{ number_format($bracket->income_from, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-gray-700">
                    {{ $bracket->income_to ? 'Rp ' . number_format($bracket->income_to, 0, ',', '.') : '∞ Tak terbatas' }}
                </td>
                <td class="px-4 py-3 text-center font-bold text-lg text-violet-700">{{ ($bracket->rate * 100) }}%</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $bracket->label }}</td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('hris.master.tax-brackets.edit', $bracket) }}" class="text-xs text-violet-600 hover:underline">Edit</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada tarif.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
