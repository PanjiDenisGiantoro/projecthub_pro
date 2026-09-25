@extends('layouts.app')
@section('title', 'Buat Campaign')
@section('page-title', 'Buat Campaign Baru')

@section('content')
<div class="py-4 w-full">
    <form method="POST" action="{{ route('campaigns.store') }}" class="fl-form"
          x-data="{ submitting: false }"
          @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
        @csrf
        @include('campaigns._form')
        <div class="fl-actions">
            <a href="{{ route('campaigns.index') }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" :disabled="submitting" class="fl-btn fl-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">Buat Campaign</span>
                <span x-show="submitting" x-cloak>Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
@endsection
