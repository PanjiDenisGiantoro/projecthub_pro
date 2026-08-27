@extends('layouts.app')
@section('title', 'Terapkan Template')
@section('page-title', 'Terapkan Template')

@section('content')
<div class="py-4 max-w-xl">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('templates.index') }}" class="hover:text-blue-600">Templates</a>
        <span class="mx-2">/</span>
        <a href="{{ route('templates.show', $template) }}" class="hover:text-blue-600">{{ $template->name }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">Terapkan</span>
    </nav>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Terapkan "{{ $template->name }}"</h2>
        <p class="text-sm text-gray-500 mb-5">Template ini akan membuat {{ $template->milestones->count() }} milestone dan {{ $template->milestones->sum(fn($m)=>$m->tasks->count()) }} task di proyek yang dipilih.</p>

        <form method="POST" action="{{ route('templates.apply.post', $template) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Proyek Target *</label>
                <select name="project_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Pilih proyek —</option>
                    @foreach($projects as $proj)
                    <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai Proyek *</label>
                <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1">Semua milestone dan task akan dijadwalkan relatif terhadap tanggal ini.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Terapkan Template</button>
                <a href="{{ route('templates.show', $template) }}" class="text-sm text-gray-600 hover:text-gray-800 px-6 py-2.5 rounded-lg border border-gray-300">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
