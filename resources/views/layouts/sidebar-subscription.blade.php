@php
    $collapsible ??= true;
    $subUser = auth()->user();
@endphp
    @php
        $subRegistrant  = $subUser->companyRegistrant() ?? $subUser;
        $subTierPkg     = $subRegistrant->packages()->where('type', 'tier')->orderByDesc('price')->first();
        $subPlanName    = $subTierPkg->name ?? 'Free';
        $subIsLifetime  = is_null($subRegistrant->active_until);
        $subIsExpired   = ! $subIsLifetime && $subRegistrant->active_until->isPast();
        $subDaysLeft    = $subIsLifetime ? null : (int) now()->startOfDay()->diffInDays($subRegistrant->active_until->copy()->startOfDay(), false);
        $subIsFree      = strtolower($subPlanName) === 'free' || empty($subTierPkg) || ($subTierPkg->price ?? 0) == 0;
        $subManageRoute = route('billing.renew');
        $subBtnLabel    = $subIsFree ? 'Upgrade ke Premium' : 'Kelola / Perpanjang';
    @endphp
    <div class="px-3 pb-3 shrink-0" @if($collapsible) x-show="!sidebarCollapsed" x-cloak @endif>
        <div class="rounded-xl p-3 ph-side-divider-t">
            <div class="flex items-center gap-2.5 mb-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                     style="background:var(--ph-nav-act-bg)">
                    <svg class="w-4 h-4" style="color:var(--ph-nav-act-fg)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
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
            <a href="{{ $subManageRoute }}"
               class="{{ $subIsFree ? 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-xs' : 'ph-sub-manage-btn' }} block text-center text-[12px] font-semibold rounded-lg py-1.5 transition-all">
                {{ $subBtnLabel }}
            </a>
        </div>
    </div>
