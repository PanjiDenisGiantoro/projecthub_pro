@php
    $collapsible ??= true;
    $subUser = auth()->user();
@endphp
@if(! $subUser->is_super_admin)
    @php
        $subRegistrant  = $subUser->companyRegistrant() ?? $subUser;
        $subTierPkg     = $subRegistrant->packages()->where('type', 'tier')->orderByDesc('price')->first();
        $subPlanName    = $subTierPkg->name ?? 'Free';
        $subIsLifetime  = is_null($subRegistrant->active_until);
        $subIsExpired   = ! $subIsLifetime && $subRegistrant->active_until->isPast();
        $subDaysLeft    = $subIsLifetime ? null : (int) now()->startOfDay()->diffInDays($subRegistrant->active_until->copy()->startOfDay(), false);
        $subCanManage   = $subUser->can('access billing') || $subUser->can('access invoices');
        $subManageRoute = $subUser->can('access billing') ? route('billing.history') : ($subUser->can('access invoices') ? route('invoices.index') : route('profile'));
    @endphp
    <div class="px-3 pb-3 shrink-0" @if($collapsible) x-show="!sidebarCollapsed" x-cloak @endif>
        <div class="rounded-xl p-3 ph-side-divider-t">
            <div class="flex items-center gap-2.5 mb-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                     style="background:var(--ph-nav-act-bg)">
                    <svg class="w-4 h-4" style="color:var(--ph-nav-act-fg)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[12.5px] font-semibold truncate" style="color:var(--ph-user-name)">{{ $subPlanName }} Plan</p>
                    <p class="text-[10.5px] truncate mt-0.5" style="color:{{ $subIsExpired ? '#f87171' : 'var(--ph-user-role)' }}">
                        @if($subIsExpired)
                            Expired
                        @elseif($subIsLifetime)
                            Lifetime access
                        @elseif($subDaysLeft <= 0)
                            Renews today
                        @else
                            Renews {{ $subRegistrant->active_until->format('d M Y') }}
                        @endif
                    </p>
                </div>
            </div>
            @if($subCanManage)
                <a href="{{ $subManageRoute }}"
                   class="ph-sub-manage-btn block text-center text-[12px] font-semibold rounded-lg py-1.5">
                    Manage
                </a>
            @elseif(! $subIsLifetime)
                <p class="text-center text-[11px]" style="color:var(--ph-user-role)">Contact admin to manage</p>
            @endif
        </div>
    </div>
@endif
