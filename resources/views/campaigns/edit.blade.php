@extends('layouts.app')
@section('title', 'Edit Campaign')
@section('page-title', 'Edit Campaign')

@section('content')
<div class="py-4 max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('campaigns.update', $campaign) }}" class="space-y-5">
            @csrf @method('PUT')
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Campaign</label>
            <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
                <select name="channel" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(['social_media'=>'Social Media','email'=>'Email','event'=>'Event','ads'=>'Ads','seo'=>'SEO','other'=>'Lainnya'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('channel',$campaign->channel) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(['draft','active','paused','completed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ old('status',$campaign->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select></div>
            </div>

            <div><label class="block text-sm font-medium text-gray-700 mb-1">Target</label>
            <textarea name="target" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('target',$campaign->target) }}</textarea></div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Budget</label>
                <input type="number" name="budget" value="{{ old('budget',$campaign->budget) }}" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Impressions</label>
                <input type="number" name="impressions" value="{{ old('impressions',$campaign->impressions) }}" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ old('start_date',$campaign->start_date?->format('Y-m-d')) }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                <input type="date" name="end_date" value="{{ old('end_date',$campaign->end_date?->format('Y-m-d')) }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan</button>
                <a href="{{ route('campaigns.show', $campaign) }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
