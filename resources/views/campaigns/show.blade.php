@extends('layouts.app')
@section('title', $campaign->name)
@section('page-title', 'Detail Campaign')

@section('content')
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('campaigns.index') }}" class="hover:text-blue-600">Campaigns</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">{{ $campaign->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">
            {{-- Info --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">{{ $campaign->name }}</h2>
                    <div class="flex gap-2">
                        <a href="{{ route('campaigns.edit', $campaign) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Channel:</span> <span class="font-medium">{{ ucfirst(str_replace('_',' ',$campaign->channel)) }}</span></div>
                    <div><span class="text-gray-500">Status:</span> <span class="font-medium">{{ ucfirst($campaign->status) }}</span></div>
                    <div><span class="text-gray-500">Budget:</span> <span class="font-medium">{{ $campaign->budget ? 'Rp '.number_format($campaign->budget,0,',','.') : '—' }}</span></div>
                    <div><span class="text-gray-500">Proyek:</span> <span class="font-medium">{{ $campaign->project->name ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Mulai:</span> <span>{{ $campaign->start_date?->format('d M Y') ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Selesai:</span> <span>{{ $campaign->end_date?->format('d M Y') ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Impressions:</span> <span class="font-medium">{{ number_format($campaign->impressions) }}</span></div>
                    <div><span class="text-gray-500">Leads:</span> <span class="font-medium">{{ $campaign->leads_count }}</span></div>
                </div>
                @if($campaign->target)
                    <div class="mt-4 text-sm"><span class="text-gray-500">Target:</span> <p class="text-gray-700 mt-1">{{ $campaign->target }}</p></div>
                @endif
            </div>

            {{-- Leads --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-gray-800">Leads ({{ $campaign->leads->count() }})</h4>
                    <button x-data @click="$dispatch('open-lead-modal')" class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-colors">
                        + Tambah Lead
                    </button>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-3 py-2 text-left">Nama</th>
                            <th class="px-3 py-2 text-left">Kontak</th>
                            <th class="px-3 py-2 text-left">Perusahaan</th>
                            <th class="px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($campaign->leads as $lead)
                        @php $lc = ['lead'=>'bg-blue-100 text-blue-700','prospect'=>'bg-yellow-100 text-yellow-700','client'=>'bg-green-100 text-green-700','lost'=>'bg-gray-100 text-gray-700']; @endphp
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-700">{{ $lead->name }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $lead->contact }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $lead->company ?? '—' }}</td>
                            <td class="px-3 py-2"><span class="badge {{ $lc[$lead->status] ?? '' }}">{{ ucfirst($lead->status) }}</span></td>
                            <td class="px-3 py-2">
                                <form method="POST" action="{{ route('leads.update', $lead) }}" class="flex gap-1">
                                    @csrf @method('PUT')
                                    <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-200 rounded px-1 py-1 focus:outline-none">
                                        @foreach(['lead','prospect','client','lost'] as $s)
                                            <option value="{{ $s }}" {{ $lead->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-3 py-4 text-center text-gray-400">Belum ada lead.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Metrics sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-4">Metrik Campaign</h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Total Leads</span><span class="font-bold text-gray-800">{{ $campaign->leads_count }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Converted</span><span class="font-bold text-green-600">{{ $campaign->leads->where('status','client')->count() }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Conversion Rate</span>
                        <span class="font-bold text-blue-600">
                            {{ $campaign->leads_count > 0 ? round($campaign->leads->where('status','client')->count()/$campaign->leads_count*100,1) : 0 }}%
                        </span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Impressions</span><span class="font-bold text-gray-800">{{ number_format($campaign->impressions) }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Lead Modal --}}
<div x-data="{open:false}" @open-lead-modal.window="open=true">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="open=false" class="absolute inset-0 bg-black/50"></div>
        <div class="relative bg-white rounded-xl shadow-xl p-6 w-full max-w-md z-10">
            <h3 class="font-semibold text-gray-800 mb-4">Tambah Lead Baru</h3>
            <form method="POST" action="{{ route('campaigns.leads.store', $campaign) }}" class="space-y-4">
                @csrf
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Kontak (Email/HP) *</label>
                <input type="text" name="contact" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Perusahaan</label>
                <input type="text" name="company" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan</button>
                    <button type="button" @click="open=false" class="text-gray-600 text-sm px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
