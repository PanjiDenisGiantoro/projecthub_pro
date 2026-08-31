@extends('layouts.app')
@section('title', $task->title)
@section('page-title', 'Detail Task')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/filepond@4/dist/filepond.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4/dist/filepond-plugin-image-preview.min.css">
@endpush

@section('content')
@php
    $pc   = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
    $sc   = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
    $plb  = ['low'=>'border-l-green-400','medium'=>'border-l-yellow-400','high'=>'border-l-orange-400','urgent'=>'border-l-red-500'];
    $user = auth()->user();
    $days     = $task->daysRemaining();
    $overdue  = $task->isOverdue();
    $timePct  = $task->timeProgressPercent();
    $borderCl = $plb[$task->priority] ?? 'border-l-gray-300';
@endphp
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-5 flex items-center gap-2">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('tasks.index', $project) }}" class="hover:text-blue-600">Tasks</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium truncate max-w-xs">{{ $task->title }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ===== MAIN COLUMN ===== --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Title card --}}
            <div class="bg-white rounded-xl border border-gray-200 border-l-4 {{ $borderCl }} p-6">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ $task->title }}</h2>
                    <div class="flex gap-2 shrink-0">
                        <span class="badge {{ $pc[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                        <span class="badge {{ $sc[$task->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                    </div>
                </div>

                @if($task->description)
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $task->description }}</p>
                @endif

                @if($task->completion_notes)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Deskripsi Penyelesaian</p>
                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line bg-green-50 border border-green-100 rounded-lg px-4 py-3">{{ $task->completion_notes }}</p>
                </div>
                @endif

                {{-- Date timeline --}}
                @if($task->start_date || $task->due_date)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-3 text-sm flex-wrap">
                        @if($task->start_date)
                        <div class="flex items-center gap-1.5 text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-gray-500">Mulai:</span>
                            <span class="font-medium">{{ $task->start_date->format('d M Y') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        @endif
                        @if($task->due_date)
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 {{ $overdue ? 'text-red-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-gray-500">Deadline:</span>
                            <span class="font-medium {{ $overdue ? 'text-red-600' : 'text-gray-700' }}">{{ $task->due_date->format('d M Y') }}</span>
                        </div>
                        @endif
                        @if($task->durationDays())
                            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full border">{{ $task->durationDays() }} hari total</span>
                        @endif
                    </div>

                    {{-- Timeline bar --}}
                    @if($task->start_date && $task->due_date)
                    @php
                        $totalDays = max(1, $task->start_date->diffInDays($task->due_date));
                        $elapsed   = min($totalDays, max(0, $task->start_date->diffInDays(now())));
                        $timelinePct = round($elapsed / $totalDays * 100);
                    @endphp
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-400 mb-1">
                            <span>{{ $task->start_date->format('d M') }}</span>
                            <span class="{{ $overdue ? 'text-red-500 font-medium' : '' }}">{{ $task->due_date->format('d M Y') }}</span>
                        </div>
                        <div class="relative w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-3 rounded-full {{ $overdue ? 'bg-red-400' : 'bg-blue-400' }} transition-all"
                                 style="width: {{ $timelinePct }}%"></div>
                        </div>
                        <div class="text-xs text-gray-400 mt-1 text-right">
                            @if($days !== null)
                                @if($overdue)
                                    <span class="text-red-500 font-medium">{{ abs($days) }} hari terlambat</span>
                                @elseif($days === 0)
                                    <span class="text-orange-500 font-medium">Deadline hari ini!</span>
                                @elseif($days <= 3)
                                    <span class="text-orange-500 font-medium">{{ $days }} hari lagi</span>
                                @else
                                    <span>{{ $days }} hari lagi</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Time Tracking --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-gray-800 text-sm">Time Tracking</h4>
                    <div class="flex items-center gap-3 text-sm">
                        @php $loggedHours = round($task->totalMinutes() / 60, 1); @endphp
                        <span class="text-gray-500">
                            <span class="font-semibold text-gray-800">{{ $loggedHours }}j</span> tercatat
                            @if($task->estimated_hours)
                                / <span class="font-medium">{{ $task->estimated_hours }}j</span> estimasi
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Time progress bar --}}
                @if($task->estimated_hours)
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>Progress waktu</span>
                        <span class="{{ $timePct >= 100 ? 'text-red-500 font-medium' : 'text-gray-500' }}">{{ $timePct }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all {{ $timePct >= 100 ? 'bg-red-400' : ($timePct >= 75 ? 'bg-orange-400' : 'bg-blue-500') }}"
                             style="width: {{ $timePct }}%"></div>
                    </div>
                </div>
                @endif

                @if($user->hasRole(['admin','member']))
                <div class="flex gap-2 mb-4">
                    @if($runningLog)
                    <form method="POST" action="{{ route('tasks.timelog.store', $task) }}">
                        @csrf
                        <input type="hidden" name="action" value="stop">
                        <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
                            Stop Timer
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('tasks.timelog.store', $task) }}">
                        @csrf
                        <input type="hidden" name="action" value="start">
                        <button type="submit" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21"/></svg>
                            Start Timer
                        </button>
                    </form>
                    @endif

                    <form method="POST" action="{{ route('tasks.timelog.store', $task) }}" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="action" value="manual">
                        <input type="number" name="minutes" min="1" placeholder="Menit..."
                               class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white text-sm px-3 py-2 rounded-lg transition-colors">Log Manual</button>
                    </form>
                </div>
                @endif

                <div class="space-y-2">
                    @forelse($task->timeLogs->sortByDesc('started_at') as $log)
                    <div class="flex items-center gap-3 text-sm p-2.5 rounded-lg {{ $log->is_running ? 'bg-green-50 border border-green-200' : 'bg-gray-50' }}">
                        @if($log->is_running)
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse shrink-0"></span>
                        @else
                            <span class="w-2 h-2 bg-gray-300 rounded-full shrink-0"></span>
                        @endif
                        <span class="font-medium text-gray-700 w-28 truncate">{{ $log->user->name }}</span>
                        <span class="text-gray-500 text-xs">{{ $log->started_at->format('d M H:i') }}</span>
                        @if($log->ended_at)
                            <span class="text-gray-400 text-xs">→ {{ $log->ended_at->format('H:i') }}</span>
                            <span class="font-semibold text-gray-700 ml-auto">{{ round($log->minutes/60,1) }}j</span>
                        @else
                            <span class="text-green-600 font-medium ml-auto text-xs">Running…</span>
                        @endif
                        @if($log->notes)
                            <span class="text-gray-400 text-xs truncate max-w-xs hidden sm:block">{{ $log->notes }}</span>
                        @endif
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 py-2">Belum ada log waktu.</p>
                    @endforelse
                </div>
            </div>

            {{-- Comments --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h4 class="font-semibold text-gray-800 text-sm mb-4">Komentar & Diskusi</h4>
                <div class="space-y-4 mb-5">
                    @forelse($task->comments->sortBy('created_at') as $comment)
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr($comment->user->name, 0, 2)) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $comment->user->name }}</span>
                                <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $comment->body }}</p>

                            @if($comment->attachments->count())
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($comment->attachments as $att)
                                    @if($att->isImage())
                                    <a href="{{ $att->url() }}" target="_blank" title="{{ $att->file_name }}"
                                       class="block w-20 h-20 rounded-lg border border-gray-200 overflow-hidden hover:opacity-80 transition-opacity">
                                        <img src="{{ $att->url() }}" alt="{{ $att->file_name }}" class="w-full h-full object-cover">
                                    </a>
                                    @else
                                    <a href="{{ $att->url() }}" target="_blank"
                                       class="flex items-center gap-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 text-blue-600 hover:underline">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span class="truncate max-w-[160px]">{{ $att->file_name }}</span>
                                        <span class="text-gray-400">({{ $att->humanSize() }})</span>
                                    </a>
                                    @endif
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">Belum ada komentar.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('tasks.comment', [$project, $task]) }}" enctype="multipart/form-data" class="space-y-3"
                      x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <textarea name="body" rows="2" placeholder="Tulis komentar..." required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    <input type="file" name="attachments[]" multiple class="filepond-comment-input">
                    <button type="submit" :disabled="submitting"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Kirim</span>
                        <span x-show="submitting" x-cloak>Mengirim...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ===== SIDEBAR ===== --}}
        <div class="space-y-4">

            {{-- Info card --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3 text-sm">
                <h4 class="font-semibold text-gray-800 text-sm border-b border-gray-100 pb-2 mb-1">Informasi</h4>
                <div class="flex justify-between">
                    <span class="text-gray-500">Proyek</span>
                    <span class="font-medium text-gray-800 text-right">{{ $project->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Milestone</span>
                    <span class="font-medium text-gray-800">{{ $task->milestone->title ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Assignee</span>
                    <span class="font-medium text-gray-800">{{ $task->assignee->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Dibuat oleh</span>
                    <span class="font-medium text-gray-800">{{ $task->creator->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Start Date</span>
                    <span class="font-medium text-gray-800">{{ $task->start_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Due Date</span>
                    <span class="font-medium {{ $overdue ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $task->due_date?->format('d M Y') ?? '—' }}
                    </span>
                </div>
                @if($task->durationDays())
                <div class="flex justify-between">
                    <span class="text-gray-500">Durasi</span>
                    <span class="font-medium text-gray-800">{{ $task->durationDays() }} hari</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500">Estimasi</span>
                    <span class="font-medium text-gray-800">{{ $task->estimated_hours ? $task->estimated_hours.' jam' : '—' }}</span>
                </div>
                @if($days !== null)
                <div class="flex justify-between items-center pt-1 border-t border-gray-100">
                    <span class="text-gray-500">Sisa waktu</span>
                    @if($overdue)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ abs($days) }} hari terlambat</span>
                    @elseif($days === 0)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">Hari ini</span>
                    @elseif($days <= 3)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">{{ $days }} hari lagi</span>
                    @else
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $days }} hari lagi</span>
                    @endif
                </div>
                @endif
            </div>

            {{-- Google Meet --}}
            @if($task->google_meet_link || (!$user->hasRole('client') && $project->google_meet_enabled))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Google Meet</h4>
                @if($task->google_meet_link)
                    <a href="{{ $task->google_meet_link }}" target="_blank" rel="noopener"
                       class="inline-block px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        Join Meeting
                    </a>
                @else
                    <form method="POST" action="{{ route('tasks.meeting.create', [$project, $task]) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-50">
                            Buat Meeting
                        </button>
                    </form>
                @endif
            </div>
            @endif

            {{-- Update Task --}}
            @if(!$user->hasRole('client'))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Update Task</h4>
                <form method="POST" action="{{ route('tasks.update', [$project, $task]) }}" class="space-y-3"
                      x-data="{ status: '{{ old('status', $task->status) }}' }">
                    @csrf @method('PUT')

                    @if($user->hasRole(['admin','member']) && $task->status !== 'done')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Assigned to</label>
                        <select name="assigned_to" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— Tidak ada —</option>
                            @foreach($developers as $dev)
                                <option value="{{ $dev->id }}" {{ $task->assigned_to === $dev->id ? 'selected' : '' }}>{{ $dev->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select name="status" x-model="status" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $s => $sl)
                                <option value="{{ $s }}" {{ $task->status === $s ? 'selected' : '' }}>{{ $sl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Deskripsi Penyelesaian <span class="text-red-500" x-show="status === 'done'">*</span>
                        </label>
                        <textarea name="completion_notes" rows="4" :required="status === 'done'"
                                  placeholder="Deskripsikan apa yang sudah dikerjakan, hambatan, atau catatan penting..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('completion_notes', $task->completion_notes) }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-3 py-2 rounded-lg transition-colors">
                        Simpan
                    </button>
                </form>
            </div>
            @endif

            {{-- Riwayat perubahan task — bisa dibuka/tutup, isinya di-lazy load lewat
                 fetch() cuma pas panel ini dibuka (lihat logs() di TaskWebController),
                 bukan ikut di-query tiap kali halaman detail task dibuka. --}}
            <aside class="rounded-xl border border-gray-200 bg-white overflow-hidden lg:sticky lg:top-5"
                   x-data="{ open: false, loading: false, loaded: false }"
                   x-init="$watch('open', value => {
                       if (!value || loaded) return;
                       loading = true;
                       fetch('{{ route('tasks.logs', [$project, $task]) }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                           .then(r => r.text())
                           .then(html => { $refs.logList.innerHTML = html; loaded = true; })
                           .catch(() => { $refs.logList.innerHTML = '<p class=&quot;px-4 py-6 text-center text-xs text-red-400&quot;>Gagal memuat riwayat.</p>'; })
                           .finally(() => { loading = false; });
                   })">
                <button type="button" @click="open = !open"
                        class="w-full flex items-center justify-between gap-2 px-4 py-3.5 text-left hover:bg-gray-50 transition-colors">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="font-semibold text-gray-800 text-sm">Riwayat Perubahan</span>
                        @if($logsCount)
                        <span class="badge bg-gray-100 text-gray-600">{{ $logsCount }}</span>
                        @endif
                    </span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" x-cloak x-transition class="border-t border-gray-100 max-h-[70vh] overflow-y-auto">
                    <p x-show="loading" class="px-4 py-6 text-center text-xs text-gray-400">Memuat riwayat...</p>
                    <div x-show="!loading" x-ref="logList" class="divide-y divide-gray-100"></div>
                </div>
            </aside>

        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2/dist/filepond-plugin-file-validate-size.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4/dist/filepond-plugin-image-preview.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/filepond@4/dist/filepond.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    FilePond.registerPlugin(FilePondPluginFileValidateSize, FilePondPluginImagePreview);

    document.querySelectorAll('.filepond-comment-input').forEach(function (input) {
        FilePond.create(input, {
            allowMultiple: true,
            maxFiles: 5,
            maxFileSize: '10MB',
            instantUpload: false,
            labelIdle: 'Seret file ke sini atau <span class="filepond--label-action">Pilih File</span> <span style="color:#9ca3af">(opsional, maks 5 file, 10MB/file)</span>',
        });
    });
});
</script>
@endpush
@endsection
