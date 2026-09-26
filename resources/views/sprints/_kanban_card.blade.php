@php
    $doneCount = $task->checklists->flatMap->items->where('is_done', true)->count();
    $totalCount = $task->checklists->flatMap->items->count();
    $pctChecklist = $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0;
    $isDone = $task->isDone() || ($col->is_done ?? false) || in_array($task->status, ['done', 'completed']);
    $overdue = !$isDone && $task->isOverdue();
    $days = $task->daysRemaining();

    $priorityBadges = [
        'urgent'    => ['class' => 'border border-rose-200 bg-rose-50/70 text-rose-600 dark:bg-rose-950/40 dark:border-rose-900/60 dark:text-rose-300', 'label' => 'Urgent', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>'],
        'critical'  => ['class' => 'border border-rose-200 bg-rose-50/70 text-rose-600 dark:bg-rose-950/40 dark:border-rose-900/60 dark:text-rose-300', 'label' => 'Urgent', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>'],
        'high'      => ['class' => 'border border-amber-200 bg-amber-50/70 text-amber-600 dark:bg-amber-950/40 dark:border-amber-900/60 dark:text-amber-300', 'label' => 'Important', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>'],
        'medium'    => ['class' => 'border border-blue-200 bg-blue-50/70 text-blue-600 dark:bg-blue-950/40 dark:border-blue-900/60 dark:text-blue-300', 'label' => 'Medium', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>'],
        'low'       => ['class' => 'border border-gray-200 bg-gray-50 text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400', 'label' => 'Low', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>'],
    ];
    $pCfg = $priorityBadges[$task->priority] ?? $priorityBadges['medium'];

    $lblPalette = [
        'ux stages'     => 'bg-rose-100/80 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-bold text-[10.5px] uppercase tracking-wider',
        'research'      => 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold text-[10.5px] uppercase tracking-wider',
        'it frontend'   => 'bg-sky-100/80 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 font-bold text-[10.5px] uppercase tracking-wider',
        'core'          => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
        'design system' => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
        'backend'       => 'bg-emerald-100/80 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold text-[10.5px] uppercase tracking-wider',
        'api'           => 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold text-[10.5px] uppercase tracking-wider',
        'frontend'      => 'bg-teal-100/80 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 font-bold text-[10.5px] uppercase tracking-wider',
        'qa'            => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
        'performance'   => 'bg-rose-100/80 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-bold text-[10.5px] uppercase tracking-wider',
        'auth'          => 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold text-[10.5px] uppercase tracking-wider',
        'data'          => 'bg-rose-100/80 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-bold text-[10.5px] uppercase tracking-wider',
        'infra'         => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
    ];

    // Gather assigned members
    $allMembers = $task->members->isNotEmpty() ? $task->members : ($task->assignee ? collect([$task->assignee]) : collect());
@endphp

<div class="kanban-card group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/90 dark:border-gray-700 shadow-2xs hover:shadow-md hover:border-blue-400/80 dark:hover:border-blue-500/80 transition-all duration-150 cursor-pointer overflow-hidden select-none mb-3"
     data-task-id="{{ $task->id }}"
     data-priority="{{ $task->priority }}"
     data-assignee-ids="{{ $allMembers->pluck('id')->join(',') }}"
     data-labels="{{ $task->labels->pluck('id')->join(',') }}"
     data-due="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
     data-is-done="{{ $isDone ? '1' : '0' }}"
     data-is-overdue="{{ $overdue ? '1' : '0' }}"
     data-sprint-id="{{ $task->sprint_id ?? '' }}"
     data-milestone-id="{{ $task->milestone_id ?? '' }}"
     data-sort-order="{{ $task->sort_order }}"
     @click="openTask({{ $task->id }})">

    {{-- Cover Image Thumbnail / Badge --}}
    @if($task->cover_image_path)
    <div class="card-cover-container relative h-32 w-full overflow-hidden bg-gray-100 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700/50">
        <img src="{{ Storage::url($task->cover_image_path) }}"
             alt="{{ $task->title }}"
             class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
             loading="lazy">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
        <div class="absolute bottom-2.5 left-2.5 flex items-center gap-1.5">
            <span class="px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold tracking-wider uppercase">
                {{ $isDone ? 'Release v2.4 Live' : 'UI PROTOTYPE' }}
            </span>
        </div>
    </div>
    @endif

    <div class="p-3.5 space-y-2.5">
        {{-- Top Row: Labels on Left, Priority Flag on Right --}}
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="card-labels-container flex flex-wrap items-center gap-1.5">
                @if($task->labels->isNotEmpty())
                    @foreach($task->labels->take(2) as $label)
                    @php
                        $lKey = strtolower(trim($label->name));
                        $lStyle = $lblPalette[$lKey] ?? null;
                        if (!$lStyle) {
                            $c = $label->colorClasses();
                            $lStyle = $c['bg'] . ' ' . $c['text'];
                        }
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-semibold {{ $lStyle }} tracking-wide">
                        {{ $label->name }}
                    </span>
                    @endforeach
                    @if($task->labels->count() > 2)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 shrink-0">
                        +{{ $task->labels->count() - 2 }}
                    </span>
                    @endif
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-medium bg-gray-50 text-gray-500 border border-gray-100">
                        Task
                    </span>
                @endif
            </div>

            {{-- Priority Flag badge --}}
            <div class="card-priority-container shrink-0">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold {{ $pCfg['class'] }}">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $pCfg['icon'] !!}
                    </svg>
                    {{ $pCfg['label'] }}
                </span>
            </div>
        </div>

        {{-- Title Row with Radio Complete Button --}}
        <div class="flex items-start gap-2.5 pt-0.5">
            {{-- Radio / Checkbox Complete Button --}}
            <button type="button"
                    @click.stop="toggleTaskComplete({{ $task->id }}, {{ $isDone ? 'false' : 'true' }})"
                    class="mt-0.5 w-4 h-4 rounded-full shrink-0 flex items-center justify-center transition-colors {{ $isDone ? 'bg-emerald-500 text-white' : 'border-2 border-gray-300 hover:border-emerald-500 text-transparent' }}"
                    title="{{ $isDone ? 'Tandai belum selesai' : 'Tandai selesai' }}">
                <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </button>

            <h4 class="card-title-text text-[13px] font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-snug line-clamp-2 {{ $isDone ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                {{ $task->title }}
            </h4>
        </div>

        {{-- Meta Counters Row (Checklist, Chat, Attachments) --}}
        <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500 pt-1">
            {{-- Checklists count --}}
            @if($totalCount > 0)
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[11px] font-semibold {{ $pctChecklist === 100 ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                {{ $doneCount }}/{{ $totalCount }}
            </span>
            @endif

            {{-- Comments count --}}
            <span class="inline-flex items-center gap-1 text-[11px] {{ $task->comments_count > 0 ? 'text-gray-500 dark:text-gray-400 font-medium' : 'text-gray-300 dark:text-gray-600' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                {{ $task->comments_count }}
            </span>

            {{-- Attachments count --}}
            @if($task->attachments_count > 0)
            <span class="inline-flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                {{ $task->attachments_count }}
            </span>
            @endif
        </div>

        {{-- Card Footer: Deadline / Due on Left, Assignee PP on Right --}}
        <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between gap-2 text-xs">
            {{-- Due Date / Overdue / Completed badge --}}
            <div class="card-due-container">
                @if($isDone)
                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3 h-3 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Completed {{ $task->updated_at?->format('d M') ?? '' }}
                    </span>
                @elseif($task->due_date)
                    @if($overdue)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-600 dark:text-red-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            {{ $task->due_date->format('d M') }} (Overdue)
                        </span>
                    @elseif($days !== null && $days <= 2)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-500 dark:text-red-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Due: {{ $task->due_date->format('d M') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                            <svg class="w-3 h-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $task->due_date->format('d M') }}
                        </span>
                    @endif
                @else
                    <span class="text-[11px] text-gray-300 dark:text-gray-600">No deadline</span>
                @endif
            </div>

            {{-- Right: Member PP Avatars Stack --}}
            <div class="card-members-container flex items-center -space-x-1.5 shrink-0">
                @forelse($allMembers->take(3) as $m)
                    @if($m->avatar ?? false)
                        <img src="{{ Storage::url($m->avatar) }}"
                             alt="{{ $m->name }}"
                             title="{{ $m->name }}"
                             class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 object-cover shadow-2xs">
                    @else
                        <div class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 text-white flex items-center justify-center text-[10px] font-bold shadow-2xs"
                             style="background-color: {{ $m->avatarColor() }};"
                             title="{{ $m->name }}">
                            {{ $m->initials() }}
                        </div>
                    @endif
                @empty
                    <span class="w-6 h-6 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-300 dark:text-gray-600 flex items-center justify-center text-[10px]" title="Belum ditugaskan">
                        +
                    </span>
                @endforelse

                @if($allMembers->count() > 3)
                <div class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center text-[10px] font-bold shadow-2xs"
                     title="+{{ $allMembers->count() - 3 }} lainnya">
                    +{{ $allMembers->count() - 3 }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
