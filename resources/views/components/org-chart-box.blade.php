@props(['unit', 'childrenCount' => null])

<div class="org-chart-box" style="border-top-color: {{ $unit->displayColor() }}">
    <div class="org-chart-box__avatar-wrap">
        @if($unit->head?->avatar)
            <img src="{{ Storage::url($unit->head->avatar) }}" alt="{{ $unit->head->name }}" class="org-chart-box__avatar-img">
        @else
            <div class="org-chart-box__avatar" style="background: {{ $unit->displayColor() }}">
                {{ strtoupper(substr($unit->head?->name ?? $unit->name, 0, 2)) }}
            </div>
        @endif
        <span class="org-chart-box__status {{ $unit->is_active ? 'is-active' : 'is-inactive' }}" title="{{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}"></span>
    </div>

    <div class="org-chart-box__name" title="{{ $unit->head?->name ?? 'Belum ada kepala unit' }}">
        {{ $unit->head?->name ?? '— Belum diisi —' }}
    </div>
    <div class="org-chart-box__title" title="{{ $unit->name }}">{{ $unit->name }}</div>
    <div class="org-chart-box__code">L{{ $unit->code }}</div>

    <div class="org-chart-box__badges">
        <span title="Unit turunan">{{ $unit->children_count ?? $childrenCount ?? 0 }} unit</span>
        <span title="Anggota">{{ $unit->users_count ?? 0 }} orang</span>
    </div>

    <div class="org-chart-box__actions">
        <a href="{{ route('organization-units.create', ['company_id' => $unit->company_id, 'parent_id' => $unit->id]) }}" title="Tambah sub-unit">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </a>
        <a href="{{ route('organization-units.edit', $unit) }}" title="Edit">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </a>
        <form method="POST" action="{{ route('organization-units.destroy', $unit) }}"
              data-confirm-delete="{{ $unit->name }}" data-confirm-label="Hapus Unit Organisasi">
            @csrf @method('DELETE')
            <button type="submit" title="Hapus">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </form>
    </div>
</div>
