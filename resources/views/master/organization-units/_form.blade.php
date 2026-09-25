{{-- Dipakai create & edit. $organizationUnit = null saat tambah baru. --}}
@php
    $unit = $organizationUnit ?? null;
    $selectedParent = $unit ? (string) $unit->parent_id : (string) request('parent_id');
    $customColor = old('color', $unit?->color);
@endphp

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Informasi Unit</h3>
        <p class="fl-section-desc">
            @if($unit)
                Kode <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded font-mono text-xs">L{{ $unit->code }}</span>
                dihitung otomatis dari posisi pada pohon &mdash; berubah jika parent dipindah.
            @else
                Posisi unit di struktur organisasi. Kode unit dihitung otomatis dari posisinya.
            @endif
        </p>
    </div>
    <div class="fl-fields">
        @if(!$unit)
            @if($companies->count() > 1)
                <div class="fl-span-2">
                    <label class="fl-label">Perusahaan <span class="fl-req">*</span></label>
                    <select name="company_id" onchange="this.form.submit()" class="fl-input">
                        <option value="">— Pilih Perusahaan —</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ (string) $selectedCompany === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ $selectedCompany }}">
            @endif
        @endif

        <div>
            <label class="fl-label" for="name">Nama Unit <span class="fl-req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $unit?->name) }}" required placeholder="Divisi IT"
                   class="fl-input @error('name') is-invalid @enderror">
            @error('name') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="fl-label">Parent <span class="text-gray-400 font-normal">(opsional)</span></label>
            <select name="parent_id" id="sel-parent" class="w-full">
                <option value="">— Tanpa Parent (Level 1) —</option>
                @foreach($tree as $node)
                    <option value="{{ $node->id }}" data-level="{{ $node->level }}" {{ $selectedParent === (string) $node->id ? 'selected' : '' }}>
                        {{ str_repeat('— ', $node->level - 1) }}{{ $node->name }} (L{{ $node->code }})
                    </option>
                @endforeach
            </select>
            <p class="fl-help">{{ $unit ? 'Memindahkan parent akan menghitung ulang kode unit ini beserta seluruh turunannya.' : 'Kosongkan untuk membuat unit level teratas (root).' }}</p>
            @error('parent_id') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="fl-label">Kepala Unit <span class="text-gray-400 font-normal">(opsional)</span></label>
            <select name="head_id" id="sel-head" class="w-full">
                <option value="">— Tidak ada —</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) old('head_id', $unit?->head_id) === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="self-end pb-2">
            <label class="fl-switch">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $unit?->is_active ?? true) ? 'checked' : '' }}>
                <span class="fl-switch-track"></span>
                <span>Unit Aktif</span>
            </label>
        </div>
    </div>
</section>

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Tampilan Bagan</h3>
        <p class="fl-section-desc">Warna kotak unit ini di Bagan Organisasi. Mode otomatis mengikuti level unit; matikan untuk memilih warna sendiri.</p>
    </div>
    <div class="fl-fields">
        <div class="fl-span-2">
            <div class="flex flex-wrap items-center gap-4">
                <label class="fl-switch">
                    <input type="checkbox" id="color-auto" onchange="toggleColorAuto(this)" {{ $customColor ? '' : 'checked' }}>
                    <span class="fl-switch-track"></span>
                    <span>Otomatis sesuai level</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-500">
                    Warna custom
                    <input type="color" name="color" id="color-input"
                           value="{{ old('color', $unit?->color ?? ($unit ? \App\Models\OrganizationUnit::defaultColorForLevel($unit->level) : '#1d4ed8')) }}"
                           class="w-10 h-9 p-0.5 border border-gray-200 rounded-lg cursor-pointer disabled:cursor-not-allowed disabled:opacity-40" {{ $customColor ? '' : 'disabled' }}>
                </label>
            </div>
            <div id="color-preview-bar" class="w-full h-10 rounded-lg flex items-center px-3 mt-3 transition-colors">
                <span id="color-preview-label" class="text-xs font-semibold"></span>
            </div>
            @error('color') <p class="fl-error">{{ $message }}</p> @enderror
        </div>
    </div>
</section>
