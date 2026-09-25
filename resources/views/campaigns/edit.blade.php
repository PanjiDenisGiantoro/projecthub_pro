@extends('layouts.app')
@section('title', 'Edit Campaign')
@section('page-title', 'Edit Campaign')

@section('content')
<div class="py-4 w-full">
    <form method="POST" action="{{ route('campaigns.update', $campaign) }}" class="fl-form">
        @csrf @method('PUT')
        @include('campaigns._form', ['campaign' => $campaign])
        <div class="fl-actions">
            <a href="{{ route('campaigns.show', $campaign) }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" class="fl-btn fl-btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
