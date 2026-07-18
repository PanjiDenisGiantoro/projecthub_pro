@props(['unit'])

<tr class="hover:bg-gray-50 transition-colors">
    <td class="px-4 py-3">
        <div class="flex items-center gap-3" style="padding-left: {{ ($unit->level - 1) * 1.5 }}rem">
            @if($unit->level > 1)
                <span class="text-gray-300">└</span>
            @endif
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr($unit->name, 0, 2)) }}
            </div>
            <div>
                <p class="font-medium text-gray-800">{{ $unit->name }}</p>
                <span class="text-xs text-gray-400 font-mono">L{{ $unit->code }}</span>
            </div>
        </div>
    </td>
    <td class="px-4 py-3 text-gray-600 text-sm">
        {{ $unit->head?->name ?? '—' }}
    </td>
    <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-50 text-blue-700 text-xs font-bold" title="Unit turunan">
            {{ $unit->children_count }}
        </span>
    </td>
    <td class="px-4 py-3 text-center text-gray-600 text-sm">
        {{ $unit->users_count }}
    </td>
    <td class="px-4 py-3 text-center">
        <span class="text-xs px-2 py-0.5 rounded-full {{ $unit->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
            {{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}
        </span>
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center gap-3 justify-end">
            <a href="{{ route('organization-units.create', ['company_id' => $unit->company_id, 'parent_id' => $unit->id]) }}"
               class="text-gray-500 hover:text-violet-600 transition-colors" title="Tambah sub-unit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </a>
            <a href="{{ route('organization-units.edit', $unit) }}" class="text-gray-500 hover:text-blue-600 transition-colors" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
            <form method="POST" action="{{ route('organization-units.destroy', $unit) }}"
                  data-confirm-delete="{{ $unit->name }}" data-confirm-label="Hapus Unit Organisasi">
                @csrf @method('DELETE')
                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Hapus">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
    </td>
</tr>
