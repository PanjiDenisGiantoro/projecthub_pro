<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->name }} — Client Portal</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full">
<div class="max-w-4xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">PH</div>
        <div>
            <p class="text-xs text-gray-400 uppercase font-semibold">Client Portal</p>
            <h1 class="text-xl font-bold text-gray-800">{{ $project->name }}</h1>
        </div>
    </div>

    @if($pt->label)
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-6 text-sm text-blue-800">
        {{ $pt->label }}
    </div>
    @endif

    {{-- Project info --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 mb-1">Status</p>
            <p class="font-semibold text-gray-800 capitalize">{{ $project->status }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 mb-1">Manager</p>
            <p class="font-semibold text-gray-800">{{ $project->manager?->name }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 mb-1">Selesai</p>
            <p class="font-semibold text-gray-800">{{ $project->end_date?->format('d M Y') ?? '—' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 mb-1">Progress</p>
            <p class="font-semibold text-gray-800">{{ $project->progress ?? 0 }}%</p>
        </div>
    </div>

    {{-- Milestones --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-5">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Milestones</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($project->milestones as $ms)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-800">{{ $ms->title }}</p>
                    <p class="text-xs text-gray-400">{{ $ms->due_date?->format('d M Y') ?? '—' }}</p>
                </div>
                @php $msColor = match($ms->status){ 'completed'=>'bg-green-100 text-green-700', 'in_progress'=>'bg-blue-100 text-blue-700', default=>'bg-gray-100 text-gray-600' }; @endphp
                <span class="text-xs px-2 py-0.5 rounded-full {{ $msColor }}">{{ ucfirst($ms->status) }}</span>
            </div>
            @empty
            <div class="px-5 py-4 text-sm text-gray-400">Tidak ada milestone.</div>
            @endforelse
        </div>
    </div>

    {{-- Tasks (recent) --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-5">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Tasks Terbaru</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($project->tasks->take(20) as $task)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $task->title }}</p>
                    <p class="text-xs text-gray-400">{{ $task->assignee?->name ?? 'Unassigned' }} · {{ $task->due_date?->format('d M Y') ?? '—' }}</p>
                </div>
                @php $tc = match($task->status){ 'done'=>'bg-green-100 text-green-700', 'in_progress'=>'bg-blue-100 text-blue-700', 'review'=>'bg-yellow-100 text-yellow-700', default=>'bg-gray-100 text-gray-600' }; @endphp
                <span class="text-xs px-2 py-0.5 rounded-full {{ $tc }}">{{ ucfirst($task->status) }}</span>
            </div>
            @empty
            <div class="px-5 py-4 text-sm text-gray-400">Tidak ada task.</div>
            @endforelse
        </div>
    </div>

    {{-- Budget (if allowed) --}}
    @if($pt->show_budget)
    <div class="bg-white rounded-xl border border-gray-200 mb-5 p-5">
        <h2 class="font-semibold text-gray-800 mb-3">Anggaran</h2>
        <div class="grid grid-cols-3 gap-4">
            <div><p class="text-xs text-gray-400">Budget</p><p class="font-bold">Rp {{ number_format($project->budget ?? 0, 0, ',', '.') }}</p></div>
            <div><p class="text-xs text-gray-400">Terpakai</p><p class="font-bold text-red-600">Rp {{ number_format($project->totalExpenses(), 0, ',', '.') }}</p></div>
            <div><p class="text-xs text-gray-400">Sisa</p><p class="font-bold text-green-600">Rp {{ number_format(($project->budget ?? 0) - $project->totalExpenses(), 0, ',', '.') }}</p></div>
        </div>
    </div>
    @endif

    {{-- Comment --}}
    @if($pt->can_comment)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="font-semibold text-gray-800 mb-3">Kirim Komentar / Feedback</h2>
        <form method="POST" action="{{ route('portal.comment', $pt->token) }}">
            @csrf
            <textarea name="message" rows="4" required placeholder="Tulis komentar atau feedback Anda..."
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-3"></textarea>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Kirim</button>
        </form>
    </div>
    @endif

    <p class="text-xs text-gray-400 text-center mt-8">Powered by ProjectHub Pro · Link ini dibuat khusus untuk Anda</p>
</div>
</body>
</html>
