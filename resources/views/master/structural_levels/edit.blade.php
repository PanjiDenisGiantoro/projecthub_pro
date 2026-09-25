@extends('layouts.app')
@section('title', 'Edit Level Struktural')
@section('page-title', 'Edit Level Struktural')

@section('content')
<div class="py-4 w-full">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('structural-levels.index') }}" class="hover:text-blue-600 transition-colors">Level Struktural</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">{{ $structuralLevel->name }}</span>
    </div>

    <form method="POST" action="{{ route('structural-levels.update', $structuralLevel) }}" class="fl-form"
          data-confirm-submit="Perbarui level struktural?" data-confirm-btn="Ya, Perbarui">
        @csrf @method('PUT')
        @include('master.structural_levels._form', ['structuralLevel' => $structuralLevel])
        <div class="fl-actions">
            <a href="{{ route('structural-levels.index') }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" class="fl-btn fl-btn-primary">Perbarui</button>
        </div>
    </form>
</div>
@endsection