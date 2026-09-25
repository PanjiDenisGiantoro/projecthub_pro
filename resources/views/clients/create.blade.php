@extends('layouts.app')
@section('title', 'Tambah Client')
@section('page-title', 'Tambah Client Baru')

@section('content')
<div class="py-4 w-full">
    <form method="POST" action="{{ route('clients.store') }}" class="fl-form">
        @csrf

        @if($errors->any())
            <div class="fl-alert fl-alert-error">{{ $errors->first() }}</div>
        @endif

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Informasi Client</h3>
                <p class="fl-section-desc">Role akan otomatis ditetapkan sebagai <strong>Client</strong>. Client hanya bisa melihat proyek yang dibagikan ke mereka.</p>
            </div>
            <div class="fl-fields">
                <div>
                    <label class="fl-label" for="name">Nama Perusahaan <span class="fl-req">*</span></label>
                    <div class="fl-input-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus placeholder="cth. PT Maju Jaya"
                               class="fl-input @error('name') is-invalid @enderror">
                    </div>
                </div>

                <div>
                    <label class="fl-label" for="email">Email <span class="fl-req">*</span></label>
                    <div class="fl-input-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="client@perusahaan.com"
                               class="fl-input @error('email') is-invalid @enderror">
                    </div>
                </div>
            </div>
        </section>

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Login &amp; Akses</h3>
                <p class="fl-section-desc">Password awal untuk client dan status akunnya.</p>
            </div>
            <div class="fl-fields">
                <div>
                    <label class="fl-label" for="password">Password <span class="fl-req">*</span></label>
                    <div class="fl-input-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <input type="password" id="password" name="password" required minlength="8"
                               class="fl-input @error('password') is-invalid @enderror">
                    </div>
                    <p class="fl-help">Minimal 8 karakter.</p>
                </div>

                <div>
                    <label class="fl-label" for="password_confirmation">Konfirmasi Password <span class="fl-req">*</span></label>
                    <div class="fl-input-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <input type="password" id="password_confirmation" name="password_confirmation" required class="fl-input">
                    </div>
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="fl-switch">
                        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('_token') ? (old('is_active') ? 'checked' : '') : 'checked' }}>
                        <span class="fl-switch-track"></span>
                        <span>Akun Aktif</span>
                    </label>
                    <p class="fl-help">Client nonaktif tidak bisa login.</p>
                </div>

                <div>
                    <label class="fl-switch">
                        <input type="checkbox" name="send_verification" value="1" id="send_verification" {{ old('send_verification') ? 'checked' : '' }}>
                        <span class="fl-switch-track"></span>
                        <span>Kirim email verifikasi</span>
                    </label>
                    <p class="fl-help">Jika aktif, client harus klik link verifikasi di emailnya sebelum bisa login. Jika tidak, akun langsung terverifikasi (sampaikan kredensial secara langsung).</p>
                </div>
            </div>
        </section>

        <div class="fl-actions">
            <a href="{{ route('clients.index') }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" class="fl-btn fl-btn-primary">Tambah Client</button>
        </div>
    </form>
</div>
@endsection
