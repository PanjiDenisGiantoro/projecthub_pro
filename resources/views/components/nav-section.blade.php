@props(['title', 'defaultOpen' => false, 'active' => false, 'class' => 'pt-2 pb-1'])
<div {{ $attributes->merge(['class' => $class . ' w-full']) }}
     x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }">
    <button type="button" @click="open = !open"
            class="ph-section-header w-full flex items-center justify-between gap-1 px-3 pb-1.5 group"
            :class="sidebarCollapsed ? 'hidden' : ''">
        <span class="flex items-center gap-1.5">
            @if($active)
            <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:var(--ph-nav-act-fg)"></span>
            @endif
            <span class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--ph-section-label)">{{ $title }}</span>
        </span>
        <span class="w-3.5 h-3.5 shrink-0 grid place-items-center rounded-full border transition-colors"
              :style="open ? 'color:var(--ph-nav-act-fg);border-color:var(--ph-nav-act-fg)' : 'color:var(--ph-section-label);border-color:var(--ph-section-label)'">
            <svg x-show="!open" class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
            </svg>
            <svg x-show="open" x-cloak class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 12h16"/>
            </svg>
        </span>
    </button>
    <div class="ph-section-divider hidden my-2 border-t border-white/10 w-8 mx-auto"
         :class="sidebarCollapsed ? '!block' : ''"></div>
    <div x-show="sidebarCollapsed ? true : open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-x-3"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-3"
         class="space-y-0.5 w-full">
        {{ $slot }}
    </div>
</div>
