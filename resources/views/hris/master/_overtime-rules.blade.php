<div class="bg-white rounded-xl border border-gray-200">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <div>
            <h3 class="font-semibold text-gray-900">Aturan Lembur</h3>
            <p class="text-xs text-gray-500 mt-0.5">Multiplier upah lembur per tipe hari — Permenaker No.5/2023</p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('hris.master.overtime-rules.reset') }}" method="POST" onsubmit="return confirm('Reset ke default Permenaker No.5/2023?')">
                @csrf
                <button class="text-xs text-amber-600 border border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-50">Reset Default</button>
            </form>
        </div>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Tipe Hari</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Jam Ke</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Multiplier</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @php $dayColors = ['weekday'=>'blue','weekend'=>'purple','holiday'=>'red']; @endphp
            @forelse($overtimeRules as $rule)
            <tr>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-{{ $dayColors[$rule->day_type] ?? 'gray' }}-100 text-{{ $dayColors[$rule->day_type] ?? 'gray' }}-700 capitalize">
                        {{ $rule->day_type }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center text-gray-600">
                    {{ $rule->hour_from }}{{ $rule->hour_to > 0 ? ' — ' . $rule->hour_to : ' dst' }}
                </td>
                <td class="px-4 py-3 text-center font-bold text-violet-700">{{ $rule->multiplier }}×</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $rule->label }}</td>
                <td class="px-4 py-3 text-center">
                    <form action="{{ route('hris.master.overtime-rules.toggle', $rule) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button class="text-xs {{ $rule->is_active ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada aturan lembur.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
