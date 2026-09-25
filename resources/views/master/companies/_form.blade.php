{{-- Dipakai create & edit. $company = null saat tambah baru. --}}
@php $company = $company ?? null; @endphp

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Identitas Perusahaan</h3>
        <p class="fl-section-desc">Logo, nama, dan kode singkat yang tampil di aplikasi dan dokumen.</p>
    </div>
    <div class="fl-fields">
        <div class="fl-span-2" x-data="{ preview: @json($company?->logo ? Storage::url($company->logo) : null) }">
            <label class="fl-label">Logo Perusahaan</label>
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden shrink-0">
                    <img x-show="preview" :src="preview" class="w-full h-full object-cover">
                    <svg x-show="!preview" class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h16M4 4h16v16H4V4z"/></svg>
                </div>
                <div class="min-w-0">
                    <input type="file" name="logo" accept="image/*"
                           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                           class="block text-sm text-gray-500 border-0 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="fl-help">JPG/PNG, maks 2MB.{{ $company ? ' Kosongkan untuk mempertahankan logo saat ini.' : '' }}</p>
                </div>
            </div>
            @error('logo') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="fl-label" for="name">Nama Perusahaan <span class="fl-req">*</span></label>
            <div class="fl-input-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <input type="text" id="name" name="name" value="{{ old('name', $company?->name) }}" required placeholder="PT. Contoh Indonesia"
                       class="fl-input @error('name') is-invalid @enderror">
            </div>
            @error('name') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="fl-label" for="code">Kode</label>
            <input type="text" id="code" name="code" value="{{ old('code', $company?->code) }}" maxlength="50" placeholder="PTCI"
                   class="fl-input font-mono uppercase @error('code') is-invalid @enderror">
            <p class="fl-help">Singkatan unik, dipakai di nomor dokumen.</p>
            @error('code') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div class="fl-span-2">
            <label class="fl-switch">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $company?->is_active ?? true) ? 'checked' : '' }}>
                <span class="fl-switch-track"></span>
                <span>Perusahaan Aktif</span>
            </label>
        </div>
    </div>
</section>

<section class="fl-section">
    <div>
        <h3 class="fl-section-title">Kontak</h3>
        <p class="fl-section-desc">Informasi kontak dan alamat kantor.</p>
    </div>
    <div class="fl-fields">
        <div>
            <label class="fl-label" for="email">Email</label>
            <div class="fl-input-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input type="email" id="email" name="email" value="{{ old('email', $company?->email) }}" placeholder="info@perusahaan.com"
                       class="fl-input @error('email') is-invalid @enderror">
            </div>
            @error('email') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="fl-label" for="phone">Telepon</label>
            <div class="fl-input-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $company?->phone) }}" placeholder="021-12345678" class="fl-input">
            </div>
        </div>

        <div class="fl-span-2">
            <label class="fl-label" for="website">Website</label>
            <div class="fl-input-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                <input type="url" id="website" name="website" value="{{ old('website', $company?->website) }}" placeholder="https://www.perusahaan.com"
                       class="fl-input @error('website') is-invalid @enderror">
            </div>
            @error('website') <p class="fl-error">{{ $message }}</p> @enderror
        </div>

        <div class="fl-span-2">
            <label class="fl-label" for="address">Alamat</label>
            <textarea id="address" name="address" rows="3" placeholder="Jl. Sudirman No. 1, Jakarta..."
                      class="fl-input resize-none">{{ old('address', $company?->address) }}</textarea>
        </div>
    </div>
</section>
