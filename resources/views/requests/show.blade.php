@extends('layouts.app')
@section('title', 'Request #' . $customerRequest->id)
@section('page-title', 'Detail Request')

@section('content')
@php
    $user = auth()->user();
    $sc = ['submitted'=>'bg-blue-100 text-blue-700','under_review'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','in_progress'=>'bg-purple-100 text-purple-700','done'=>'bg-gray-100 text-gray-700'];
    $pc = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
@endphp
<div class="py-4 max-w-3xl">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('requests.index') }}" class="hover:text-blue-600">Requests</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">{{ $customerRequest->title }}</span>
    </nav>

    <div class="space-y-5">
        {{-- Detail --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ $customerRequest->title }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $customerRequest->project->name }} · Oleh {{ $customerRequest->customer->name }}</p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <span class="badge {{ $pc[$customerRequest->priority] ?? '' }}">{{ ucfirst($customerRequest->priority) }}</span>
                    <span class="badge {{ $sc[$customerRequest->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$customerRequest->status)) }}</span>
                </div>
            </div>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $customerRequest->description }}</p>

            @if($customerRequest->rejection_reason)
            <div class="mt-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
                <strong>Alasan Penolakan:</strong> {{ $customerRequest->rejection_reason }}
            </div>
            @endif

            @if($customerRequest->marketing_notes)
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700">
                <strong>Catatan Marketing:</strong> {{ $customerRequest->marketing_notes }}
            </div>
            @endif
        </div>

        {{-- Approval Chain --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Alur Approval</h4>
            <div class="flex items-center gap-2 text-sm flex-wrap">
                @php
                    $steps = [
                        ['label'=>'Customer', 'done'=>true],
                        ['label'=>'Marketing Review', 'done'=>in_array($customerRequest->status, ['under_review','approved','rejected','in_progress','done'])],
                        ['label'=>'Manager Approval', 'done'=>in_array($customerRequest->status, ['approved','in_progress','done'])],
                        ['label'=>'Developer', 'done'=>in_array($customerRequest->status, ['in_progress','done'])],
                        ['label'=>'Done', 'done'=>$customerRequest->status==='done'],
                    ];
                @endphp
                @foreach($steps as $i => $step)
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs {{ $step['done'] ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                                {{ $step['done'] ? '✓' : $i+1 }}
                            </div>
                            <span class="{{ $step['done'] ? 'text-green-700 font-medium' : 'text-gray-500' }}">{{ $step['label'] }}</span>
                        </div>
                        @if($i < count($steps)-1)
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        @if($user->hasRole('marketing') && $customerRequest->status === 'submitted')
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Teruskan ke Manager</h4>
            <form method="POST" action="{{ route('requests.review', $customerRequest) }}" class="space-y-3">
                @csrf @method('PUT')
                <textarea name="marketing_notes" rows="2" placeholder="Catatan marketing (opsional)..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Teruskan ke Manager</button>
            </form>
        </div>
        @endif

        @if($user->hasRole(['admin','manager']) && in_array($customerRequest->status, ['submitted','under_review']))
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Keputusan Manager</h4>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('requests.approve', $customerRequest) }}">
                    @csrf @method('PUT')
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">✓ Setujui</button>
                </form>
                <form method="POST" action="{{ route('requests.reject', $customerRequest) }}" x-data="{open:false}" @submit.prevent="if(document.getElementById('rej_reason').value.trim()===''){alert('Alasan wajib diisi');return;}$el.submit()">
                    <button type="button" @click="open=!open" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">✗ Tolak</button>
                    <div x-show="open" x-cloak class="mt-3">
                        @csrf @method('PUT')
                        <textarea id="rej_reason" name="rejection_reason" rows="2" placeholder="Alasan penolakan (wajib)..." required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 mb-2"></textarea>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">Konfirmasi Tolak</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
