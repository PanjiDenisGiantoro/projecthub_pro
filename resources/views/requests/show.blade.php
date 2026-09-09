@extends('layouts.app')
@section('title', 'Request #' . $customerRequest->id)
@section('page-title', 'Request Details')

@section('content')
    @php
        $user = auth()->user();
        $sc = ['waiting_approval' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700', 'done' => 'bg-gray-100 text-gray-700'];
        $sl = ['waiting_approval' => 'Waiting for Approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'done' => 'Done'];
        $pc = ['low' => 'bg-green-100 text-green-700', 'medium' => 'bg-yellow-100 text-yellow-700', 'high' => 'bg-orange-100 text-orange-700', 'urgent' => 'bg-red-100 text-red-700'];
    @endphp
    <div class="py-4 max-w-3xl">
        <nav class="text-sm text-gray-500 mb-4">
            <a href="{{ route('requests.index') }}" class="hover:text-blue-600 cursor-pointer">Requests</a>
            <span class="mx-2">/</span>
            <span class="text-gray-700">{{ $customerRequest->title }}</span>
        </nav>

        <div class="space-y-5">
            {{-- Detail --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">{{ $customerRequest->title }}</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $customerRequest->project->name }} · By
                            {{ $customerRequest->customer->name }}
                        </p>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <span
                            class="badge {{ $pc[$customerRequest->priority] ?? '' }}">{{ ucfirst($customerRequest->priority) }}</span>
                        <span
                            class="badge {{ $sc[$customerRequest->status] ?? '' }}">{{ $sl[$customerRequest->status] ?? ucfirst(str_replace('_', ' ', $customerRequest->status)) }}</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $customerRequest->description }}</p>

                @if($customerRequest->rejection_reason)
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
                        <strong>Rejection Reason:</strong> {{ $customerRequest->rejection_reason }}
                    </div>
                @endif

                @if($customerRequest->marketing_notes)
                    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700">
                        <strong>Marketing Notes:</strong> {{ $customerRequest->marketing_notes }}
                    </div>
                @endif
            </div>

            {{-- Approval Chain --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-4">Approval Flow</h4>
                <div class="flex items-center gap-2 text-sm flex-wrap">
                    @php
                        $rejected = $customerRequest->status === 'rejected';
                        $steps = [
                            ['label' => 'Client', 'done' => true, 'bad' => false],
                            ['label' => 'Waiting for Approval', 'done' => in_array($customerRequest->status, ['approved', 'done']), 'bad' => $rejected],
                            ['label' => 'Done', 'done' => $customerRequest->status === 'done', 'bad' => false],
                        ];
                    @endphp
                    @foreach($steps as $i => $step)
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <div
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs
                                                {{ $step['bad'] ? 'bg-red-500 text-white' : ($step['done'] ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                                    {{ $step['bad'] ? '✗' : ($step['done'] ? '✓' : $i + 1) }}
                                </div>
                                <span
                                    class="{{ $step['bad'] ? 'text-red-700 font-medium' : ($step['done'] ? 'text-green-700 font-medium' : 'text-gray-500') }}">
                                    {{ $step['bad'] ? 'Rejected' : $step['label'] }}
                                </span>
                            </div>
                            @if($i < count($steps) - 1)
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            @if($user->hasRole(['admin', 'member']) && $customerRequest->status === 'waiting_approval')
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Manager Decision</h4>
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('requests.approve', $customerRequest) }}">
                            @csrf @method('PUT')
                            <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors cursor-pointer">✓
                                Approve</button>
                        </form>
                        <form method="POST" action="{{ route('requests.reject', $customerRequest) }}" x-data="{open:false}"
                            @submit.prevent="if(document.getElementById('rej_reason').value.trim()===''){alert('Rejection reason is required');return;}$el.submit()">
                            <button type="button" @click="open=!open"
                                class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors cursor-pointer">✗
                                Reject</button>
                            <div x-show="open" x-cloak class="mt-3">
                                @csrf @method('PUT')
                                <textarea id="rej_reason" name="rejection_reason" rows="2"
                                    placeholder="Rejection reason (required)..." required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 mb-2"></textarea>
                                <button type="submit"
                                    class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg transition-colors cursor-pointer">Confirm
                                    Rejection</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if($user->hasRole(['admin', 'member']) && $customerRequest->status === 'approved')
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Mark as Done</h4>
                    <form method="POST" action="{{ route('requests.complete', $customerRequest) }}">
                        @csrf @method('PUT')
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors cursor-pointer">Mark as Done</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection