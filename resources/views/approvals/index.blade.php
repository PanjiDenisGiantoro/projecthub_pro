@extends('layouts.app')
@section('title', 'Approvals')
@section('page-title', 'Approval Center')

@section('content')
<div class="py-4" x-data="{ tab: '{{ request('tab','pending') }}', approveModal: null, rejectModal: null }">

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ session('error') }}
    </div>
    @endif
    @if(session('error_msg'))
    <div class="mb-4 flex items-center gap-3 bg-orange-50 border border-orange-200 text-orange-700 px-4 py-3 rounded-lg text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error_msg') }}
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-yellow-200 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending_for_me'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Menunggu Saya</div>
        </div>
        <div class="bg-white rounded-xl border border-blue-200 p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['my_pending'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Request Saya Pending</div>
        </div>
        <div class="bg-white rounded-xl border border-green-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $stats['my_approved'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Disetujui</div>
        </div>
        <div class="bg-white rounded-xl border border-red-200 p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $stats['my_rejected'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Ditolak</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 bg-gray-100 rounded-lg p-1 mb-4 w-fit">
        <button @click="tab='pending'" :class="tab==='pending' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium rounded-md transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Menunggu Keputusan Saya
            @if($stats['pending_for_me'] > 0)
            <span class="bg-yellow-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $stats['pending_for_me'] }}</span>
            @endif
        </button>
        <button @click="tab='mine'" :class="tab==='mine' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium rounded-md transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Permintaan Saya
        </button>
    </div>

    {{-- Tab: Pending for Me --}}
    <div x-show="tab==='pending'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm">Menunggu Keputusan Saya</h3>
                <span class="text-xs text-gray-400">{{ $pendingForMe->total() }} item</span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Aksi / Modul</th>
                        <th class="px-4 py-3 text-left">Objek</th>
                        <th class="px-4 py-3 text-left">Diminta Oleh</th>
                        <th class="px-4 py-3 text-left">Flow</th>
                        <th class="px-4 py-3 text-left">Deadline</th>
                        <th class="px-4 py-3 text-left">Progress</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pendingForMe as $approval)
                    @php
                        $isExpiringSoon = $approval->expires_at && $approval->expires_at->diffInHours(now()) <= 2 && $approval->expires_at->isFuture();
                        $totalSteps = $approval->steps->count();
                        $doneSteps  = $approval->steps->where('status', 'approved')->count();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ str_replace('_', ' ', ucfirst($approval->action)) }}</div>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $approval->policy->module }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs">
                            @if($approval->approvable)
                                <span class="text-xs text-gray-500">#{{ $approval->approvable_id }}</span>
                                <div class="truncate text-sm">{{ $approval->approvable->title ?? class_basename($approval->approvable_type) }}</div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs">
                                    {{ strtoupper(substr($approval->requester->name ?? '?', 0, 2)) }}
                                </div>
                                <span class="text-gray-700 text-sm">{{ $approval->requester->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php $flowColors = ['sequential'=>'bg-purple-100 text-purple-700','parallel_all'=>'bg-blue-100 text-blue-700','any_of'=>'bg-teal-100 text-teal-700','single'=>'bg-gray-100 text-gray-700']; @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $flowColors[$approval->policy->flow_type] ?? '' }}">
                                {{ str_replace('_', ' ', $approval->policy->flow_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($approval->expires_at)
                                <div class="{{ $isExpiringSoon ? 'text-red-600 font-semibold' : 'text-gray-600' }} text-sm">
                                    {{ $approval->expires_at->diffForHumans() }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $approval->expires_at->format('d M, H:i') }}</div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-1.5 w-16">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $totalSteps > 0 ? round($doneSteps / $totalSteps * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $doneSteps }}/{{ $totalSteps }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                {{-- Approve --}}
                                <button @click="approveModal = {{ $approval->id }}" class="inline-flex items-center gap-1 text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Setujui
                                </button>
                                {{-- Reject --}}
                                <button @click="rejectModal = {{ $approval->id }}" class="inline-flex items-center gap-1 text-xs bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Tolak
                                </button>
                            </div>

                            {{-- Approve Modal --}}
                            <div x-show="approveModal === {{ $approval->id }}" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                <div @click.outside="approveModal = null" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                                    <h3 class="font-semibold text-gray-800 mb-1">Setujui Permintaan</h3>
                                    <p class="text-sm text-gray-500 mb-4">
                                        <strong>{{ str_replace('_', ' ', ucfirst($approval->action)) }}</strong>
                                        oleh {{ $approval->requester->name ?? '—' }}
                                    </p>
                                    <form method="POST" action="{{ route('approvals.approve', $approval) }}">
                                        @csrf @method('PUT')
                                        <textarea name="notes" rows="3" placeholder="Catatan (opsional)..."
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 mb-4"></textarea>
                                        <div class="flex gap-2 justify-end">
                                            <button type="button" @click="approveModal = null" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Batal</button>
                                            <button type="submit" class="px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">Setujui</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Reject Modal --}}
                            <div x-show="rejectModal === {{ $approval->id }}" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                <div @click.outside="rejectModal = null" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                                    <h3 class="font-semibold text-gray-800 mb-1">Tolak Permintaan</h3>
                                    <p class="text-sm text-gray-500 mb-4">Berikan alasan penolakan.</p>
                                    <form method="POST" action="{{ route('approvals.reject', $approval) }}">
                                        @csrf @method('PUT')
                                        <textarea name="notes" rows="3" placeholder="Alasan penolakan (wajib)..." required
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 mb-4"></textarea>
                                        <div class="flex gap-2 justify-end">
                                            <button type="button" @click="rejectModal = null" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Batal</button>
                                            <button type="submit" class="px-4 py-2 text-sm bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium">Tolak</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Tidak ada approval yang menunggu keputusan Anda.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($pendingForMe->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $pendingForMe->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Tab: My Requests --}}
    <div x-show="tab==='mine'">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm">Permintaan yang Saya Ajukan</h3>
                <span class="text-xs text-gray-400">{{ $myRequests->total() }} item</span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Aksi / Modul</th>
                        <th class="px-4 py-3 text-left">Objek</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Progress Steps</th>
                        <th class="px-4 py-3 text-left">Diajukan</th>
                        <th class="px-4 py-3 text-left">Diputuskan</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($myRequests as $approval)
                    @php
                        $sc = ['pending'=>'bg-yellow-100 text-yellow-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','expired'=>'bg-gray-100 text-gray-500','cancelled'=>'bg-gray-100 text-gray-400'];
                        $stepIcons = ['pending'=>'⏳','approved'=>'✅','rejected'=>'❌','skipped'=>'—'];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ str_replace('_', ' ', ucfirst($approval->action)) }}</div>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $approval->policy->module }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs">
                            @if($approval->approvable)
                                <span class="text-xs text-gray-400">#{{ $approval->approvable_id }}</span>
                                <div class="truncate">{{ $approval->approvable->title ?? class_basename($approval->approvable_type) }}</div>
                            @else <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full font-medium {{ $sc[$approval->status] ?? '' }}">
                                {{ ucfirst($approval->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                @foreach($approval->steps as $step)
                                <div title="{{ $step->approver_role }} — {{ $step->status }}"
                                     class="text-xs px-2 py-0.5 rounded {{ $step->status === 'approved' ? 'bg-green-100 text-green-700' : ($step->status === 'rejected' ? 'bg-red-100 text-red-700' : ($step->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-400')) }}">
                                    {{ $step->approver_role }}
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $approval->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $approval->decided_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($approval->status === 'pending')
                            <form method="POST" action="{{ route('approvals.cancel', $approval) }}"
                                  onsubmit="return confirm('Batalkan permintaan approval ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-gray-400 hover:text-red-500 transition-colors">Batalkan</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Anda belum mengajukan permintaan approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($myRequests->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $myRequests->links() }}</div>
            @endif
        </div>
    </div>

</div>
@endsection
