@extends('layouts.app')
@section('title', 'Tambah Perusahaan')
@section('page-title', 'Tambah Perusahaan')

@section('content')
<div class="py-4 w-full">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('companies.index') }}" class="hover:text-blue-600 transition-colors">Perusahaan</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">Tambah</span>
    </div>

    <form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data" class="fl-form"
          data-confirm-submit="Simpan perusahaan baru?" data-confirm-btn="Ya, Simpan">
        @csrf
        @include('master.companies._form')
        <div class="fl-actions">
            <a href="{{ route('companies.index') }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" class="fl-btn fl-btn-primary">Simpan Perusahaan</button>
        </div>
    </form>
</div>
@endsection
