{{-- Dipakai create & edit. $structuralLevel = null saat tambah baru. --}}
@php $level = $structuralLevel ?? null; @endphp

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Level Struktural</h3>
        <p class="fl-section-desc">Jenjang jabatan karyawan. Urutan menentukan tinggi-rendahnya level (Staff = 1, BOD = 8).</p>
    </div>
    <div class="fl-fields">
        <div>
            <label class="fl-label" for="name">Nama Level <span class="fl-req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $level?->name) }}" required placeholder="Contoh: Senior Manager"
                   class="fl-input @error('name') is-invalid @enderror">
            @error('name') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="fl-label" for="sort_order">Urutan <span class="fl-req">*</span></label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $level?->sort_order ?? $nextOrder ?? 1) }}" required min="0"
                   class="fl-input @error('sort_order') is-invalid @enderror">
            <p class="fl-help">Angka lebih kecil = level lebih rendah.</p>
            @error('sort_order') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div class="fl-span-2">
            <label class="fl-switch">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $level?->is_active ?? true) ? 'checked' : '' }}>
                <span class="fl-switch-track"></span>
                <span>Level Aktif</span>
            </label>
        </div>
    </div>
</section>
