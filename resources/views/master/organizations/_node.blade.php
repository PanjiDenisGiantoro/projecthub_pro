@php
    $levelBadges = [
        ['bg' => 'bg-blue-50',    'text' => 'text-blue-700'],
        ['bg' => 'bg-violet-50',  'text' => 'text-violet-700'],
        ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-700'],
        ['bg' => 'bg-teal-50',    'text' => 'text-teal-700'],
        ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
        ['bg' => 'bg-amber-50',   'text' => 'text-amber-700'],
        ['bg' => 'bg-rose-50',    'text' => 'text-rose-700'],
        ['bg' => 'bg-cyan-50',    'text' => 'text-cyan-700'],
    ];
    $badge = $levelBadges[$depth % count($levelBadges)];
@endphp
<div class="{{ $depth > 0 ? 'ml-5 pl-5 border-l-2 border-gray-100' : '' }}">
    <div class="flex items-center gap-2 group py-1.5">
        <span class="text-[10px] px-1.5 py-0.5 rounded font-mono {{ $badge['bg'] }} {{ $badge['text'] }} flex-shrink-0">L{{ $depth + 1 }}</span>
        <span class="font-medium text-gray-800 text-sm">{{ $node->name }}</span>
        @if($node->code)
            <span class="text-xs text-gray-400 font-mono">{{ $node->code }}</span>
        @endif
        @if(!$node->is_active)
            <span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-400 rounded">Nonaktif</span>
        @endif
        @if($node->head)
            <div class="flex items-center gap-1 text-xs text-gray-400">
                <div class="w-4 h-4 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                    {{ strtoupper(substr($node->head->name, 0, 1)) }}
                </div>
                <span>{{ explode(' ', $node->head->name)[0] }}</span>
            </div>
        @endif
        <span class="text-xs text-gray-400 ml-auto">{{ $node->childNodes->count() }} sub-unit</span>
        <a href="{{ route('organizations.create', ['company_id' => $node->company_id, 'parent_id' => $node->id]) }}"
           class="text-xs text-gray-400 hover:text-emerald-600 transition-colors opacity-0 group-hover:opacity-100">+ Sub</a>
        <a href="{{ route('organizations.edit', $node) }}" class="text-xs text-gray-400 hover:text-blue-600 transition-colors opacity-0 group-hover:opacity-100">Edit</a>
    </div>

    @if($node->childNodes->isNotEmpty())
        <div class="space-y-0.5">
            @foreach($node->childNodes as $child)
                @include('master.organizations._node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
