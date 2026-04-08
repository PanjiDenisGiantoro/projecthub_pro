@extends('layouts.app')
@section('title', 'Campaigns')
@section('page-title', 'Marketing Campaigns')

@section('content')
<div class="py-4">
    <div class="flex justify-between items-center mb-4">
        <form method="GET" class="flex gap-2">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach(['draft','active','paused','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('campaigns.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Campaign
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($campaigns as $c)
        @php
            $sc = ['draft'=>'bg-gray-100 text-gray-700','active'=>'bg-green-100 text-green-700','paused'=>'bg-yellow-100 text-yellow-700','completed'=>'bg-blue-100 text-blue-700','cancelled'=>'bg-red-100 text-red-700'];
            $ch = ['social_media'=>'📱','email'=>'📧','event'=>'🎪','ads'=>'📢','seo'=>'🔍','other'=>'🔗'];
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <span class="text-lg">{{ $ch[$c->channel] ?? '📌' }}</span>
                    <span class="ml-2 text-sm font-medium text-gray-500 uppercase">{{ str_replace('_',' ',$c->channel) }}</span>
                </div>
                <span class="badge {{ $sc[$c->status] ?? '' }}">{{ ucfirst($c->status) }}</span>
            </div>
            <h3 class="font-semibold text-gray-800 mb-1">{{ $c->name }}</h3>
            @if($c->project)
                <p class="text-xs text-gray-500 mb-3">Proyek: {{ $c->project->name }}</p>
            @endif
            <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 mb-4">
                <div>💰 Budget: <span class="font-medium">{{ $c->budget ? 'Rp '.number_format($c->budget,0,',','.') : '—' }}</span></div>
                <div>👥 Leads: <span class="font-medium">{{ $c->leads_count }}</span></div>
                <div>📅 Mulai: <span class="font-medium">{{ $c->start_date?->format('d M Y') ?? '—' }}</span></div>
                <div>📅 Selesai: <span class="font-medium">{{ $c->end_date?->format('d M Y') ?? '—' }}</span></div>
            </div>
            <a href="{{ route('campaigns.show', $c) }}" class="block text-center text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Detail →</a>
        </div>
        @empty
        <div class="col-span-3 text-center py-12 text-gray-400">Belum ada campaign.</div>
        @endforelse
    </div>
    @if($campaigns->hasPages())
        <div class="mt-4">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
