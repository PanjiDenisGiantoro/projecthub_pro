@php
    $doneCount = $task->checklists->flatMap->items->where('is_done', true)->count();
    $totalCount = $task->checklists->flatMap->items->count();
    $pctChecklist = $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0;
    $overdue = $task->isOverdue();
    $days = $task->daysRemaining();

    $priorityBadges = [
        'urgent' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900/50',
        'high'   => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-900/50',
        'medium' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/50',
        'low'    => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50',
    ];

    $priorityIcons = [
        'urgent' => '<svg class="w-3 h-3 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.547 1.042-.716 1.603a11.1 11.1 0 00-.472 2.368c-.147.164-.306.32-.477.466-.45.385-.974.68-1.547.88a6.974 6.974 0 00-1.636.852 6.953 6.953 0 00-2.072 2.768A7.054 7.054 0 002.5 15a7.002 7.002 0 0013.924.965 7.003 7.003 0 00-4.029-7.982c.004-.047.01-.094.015-.14a7.99 7.99 0 00-.014-5.29zM10 8a4 4 0 100 8 4 4 0 000-8z" clip-rule="evenodd"/></svg>',
        'high'   => '<svg class="w-3 h-3 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>',
        'medium' => '<svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14"/></svg>',
        'low'    => '<svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>',
    ];

    // Gather assigned members
    $allMembers = $task->members->isNotEmpty() ? $task->members : ($task->assignee ? collect([$task->assignee]) : collect());
@endphp

<div class="kanban-card group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200/90 dark:border-gray-700/80 shadow-[0_1px_3px_rgba(0,0,0,0.06)] hover:shadow-md hover:border-blue-400/80 dark:hover:border-blue-500/80 transition-all duration-150 cursor-pointer overflow-hidden select-none"
     data-task-id="{{ $task->id }}"
     data-sort-order="{{ $task->sort_order }}"
     @click="openTask({{ $task->id }})">

    {{-- Cover Image Thumbnail --}}
    @if($task->cover_image_path)
    <div class="h-28 w-full overflow-hidden bg-gray-100 dark:bg-gray-900 relative border-b border-gray-100 dark:border-gray-700/50">
        <img src="{{ Storage::url($task->cover_image_path) }}"
             alt="{{ $task->title }}"
             class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
             loading="lazy">
        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    </div>
    @endif

    <div class="p-3.5 space-y-2.5">
        {{-- Labels Row --}}
        @if($task->labels->isNotEmpty())
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach($task->labels as $label)
            @php $c = $label->colorClasses(); @endphp
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium {{ $c['bg'] }} {{ $c['text'] }} tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full {{ $c['dot'] }}"></span>
                {{ $label->name }}
            </span>
            @endforeach
        </div>
        @endif

        {{-- Title and Quick Sprint Action --}}
        <div class="flex items-start justify-between gap-2">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-snug line-clamp-2">
                {{ $task->title }}
            </h4>

            @if(isset($sprint) && !auth()->user()->hasRole('client'))
            <form method="POST"
                  action="{{ route('sprints.tasks.remove', [$project, $sprint]) }}"
                  data-confirm-submit="Keluarkan task &quot;{{ $task->title }}&quot; dari sprint ini ke backlog?"
                  data-confirm-btn="Ya, Keluarkan"
                  @click.stop
                  class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                @csrf
                @method('DELETE')
                <input type="hidden" name="task_id" value="{{ $task->id }}">
                <button type="submit"
                        title="Keluarkan dari sprint (kembali ke backlog)"
                        class="p-1 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </form>
            @endif
        </div>

        {{-- Checklist Progress (if items exist) --}}
        @if($totalCount > 0)
        <div class="space-y-1">
            <div class="flex items-center justify-between text-[11px] font-medium text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 {{ $pctChecklist === 100 ? 'text-emerald-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Checklist
                </span>
                <span class="{{ $pctChecklist === 100 ? 'text-emerald-600 font-semibold' : '' }}">{{ $doneCount }}/{{ $totalCount }}</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-300 {{ $pctChecklist === 100 ? 'bg-emerald-500' : 'bg-blue-500' }}"
                     style="width: {{ $pctChecklist }}%"></div>
            </div>
        </div>
        @endif

        {{-- Card Meta & Members Footer --}}
        <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between gap-2 text-xs">
            {{-- Left badges: Priority, Due Date, Attachments, Comments --}}
            <div class="flex items-center gap-2 flex-wrap text-gray-500 dark:text-gray-400">
                {{-- Priority --}}
                @if($task->priority)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium border {{ $priorityBadges[$task->priority] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}"
                      title="Prioritas: {{ ucfirst($task->priority) }}">
                    {!! $priorityIcons[$task->priority] ?? '' !!}
                    {{ ucfirst($task->priority) }}
                </span>
                @endif

                {{-- Due Date --}}
                @if($task->due_date)
                <span class="inline-flex items-center gap-1 text-[11px] font-medium {{ $overdue ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 px-1.5 py-0.5 rounded border border-red-200 dark:border-red-900/50' : ($days !== null && $days <= 2 ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded' : 'text-gray-500') }}"
                      title="Jatuh tempo: {{ $task->due_date->format('d M Y') }}">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $task->due_date->format('d M') }}
                </span>
                @endif

                {{-- Attachments count --}}
                @if($task->attachments_count > 0)
                <span class="inline-flex items-center gap-0.5 text-[11px] text-gray-500 hover:text-gray-700" title="{{ $task->attachments_count }} lampiran">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    {{ $task->attachments_count }}
                </span>
                @endif

                {{-- Comments count --}}
                @if($task->comments_count > 0)
                <span class="inline-flex items-center gap-0.5 text-[11px] text-gray-500 hover:text-gray-700" title="{{ $task->comments_count }} komentar">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    {{ $task->comments_count }}
                </span>
                @endif
            </div>

            {{-- Right: Member Avatars Stack --}}
            <div class="flex items-center -space-x-1.5 shrink-0">
                @forelse($allMembers->take(3) as $m)
                    @if($m->avatar ?? false)
                        <img src="{{ Storage::url($m->avatar) }}"
                             alt="{{ $m->name }}"
                             title="{{ $m->name }}"
                             class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 object-cover">
                    @else
                        @php
                            $names = explode(' ', trim($m->name));
                            $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                        @endphp
                        <div class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-[10px] font-bold"
                             title="{{ $m->name }}">
                            {{ $initials }}
                        </div>
                    @endif
                @empty
                    {{-- Unassigned indicator --}}
                    <span class="w-6 h-6 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 flex items-center justify-center text-[10px]" title="Belum ditugaskan">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                @endforelse

                @if($allMembers->count() > 3)
                <div class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center text-[10px] font-semibold"
                     title="+{{ $allMembers->count() - 3 }} lainnya">
                    +{{ $allMembers->count() - 3 }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
