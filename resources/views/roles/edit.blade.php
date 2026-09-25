@extends('layouts.app')
@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
<div class="py-4 w-full">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('roles.index') }}" class="hover:text-blue-600 transition-colors">Manajemen Role</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">{{ \App\Support\RoleLabel::for($role->name) }}</span>
    </div>

    <form method="POST" action="{{ route('roles.update', $role) }}" class="fl-form">
        @csrf @method('PUT')
        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Detail Role</h3>
                <p class="fl-section-desc">Role ini digunakan oleh <strong>{{ $role->users_count ?? $role->users()->count() }}</strong> user. Mengubah nama role akan mempengaruhi semua user yang memiliki role ini.</p>
            </div>
            <div class="fl-fields">
                <div>
                    <label class="fl-label" for="name">Nama Role <span class="fl-req">*</span></label>
                    <div class="fl-input-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required
                               class="fl-input @error('name') is-invalid @enderror">
                    </div>
                    <p class="fl-help">Hanya huruf, angka, tanda hubung, dan underscore. Disimpan dalam huruf kecil.</p>
                    @error('name') <p class="fl-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>
        <div class="fl-actions">
            <a href="{{ route('roles.index') }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" class="fl-btn fl-btn-primary">Perbarui</button>
        </div>
    </form>
</div>
@endsection
