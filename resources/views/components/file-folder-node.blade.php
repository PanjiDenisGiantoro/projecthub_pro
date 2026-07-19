@props(['name', 'node', 'files'])

<div x-data="{ open: true }">
    <div class="flex items-center gap-1">
        @if(count($node['children']) > 0)
            <button type="button" @click="open = !open" class="text-gray-400 hover:text-gray-600 w-4 shrink-0">
                <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        @else
            <span class="w-4 shrink-0"></span>
        @endif
        <button type="button" @click="activeFolder = '{{ $node['path'] }}'"
                :class="activeFolder === @js($node['path']) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'"
                class="flex-1 min-w-0 text-left px-2 py-1.5 rounded-lg text-sm flex items-center gap-2 transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <span class="truncate">{{ $name }}</span>
            <span class="text-xs text-gray-400 ml-auto shrink-0">({{ $files->where('folder', $node['path'])->count() }})</span>
        </button>
    </div>
    @if(count($node['children']) > 0)
        <div x-show="open" x-cloak class="ml-4 border-l border-gray-100 pl-2 mt-0.5 space-y-0.5">
            @foreach($node['children'] as $childName => $childNode)
                <x-file-folder-node :name="$childName" :node="$childNode" :files="$files" />
            @endforeach
        </div>
    @endif
</div>
