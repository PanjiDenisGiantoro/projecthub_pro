{{-- ── Helpers ──────────────────────────────────────────────────────────── --}}
@php
    $active       = 'ph-nav-link ph-active';
    $inactive     = 'ph-nav-link';
    $isSuperAdmin = auth()->user()->is_super_admin;
    $userPkgs     = $isSuperAdmin ? ['task_management', 'hris'] : auth()->user()->activePackages();
    $activePkg    = session('active_package', $userPkgs[0] ?? 'task_management');
    $activePkg    = is_string($activePkg) ? $activePkg : 'task_management'; // guard: jangan sampai object masuk session
    $showTm       = empty($userPkgs) || $activePkg === 'task_management';
    $showHris     = $activePkg === 'hris';
@endphp

{{-- Dashboard --}}
@can('access dashboard')
<a href="{{ route('dashboard') }}"
   class="{{ request()->routeIs('dashboard') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    Dashboard
</a>
@endcan

{{-- ══ TASK MANAGEMENT NAV ══════════════════════════════════════════════════ --}}
@if($showTm)

{{-- Projects --}}
@can('access projects')
<a href="{{ route('projects.index') }}"
   class="{{ request()->routeIs('projects.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
    </svg>
    Proyek
</a>
@endcan

{{-- Bug Tickets --}}
@can('access tickets')
<a href="{{ route('tickets.all') }}"
   class="{{ request()->routeIs('tickets.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
    </svg>
    Bug Tickets
</a>
@endcan

{{-- Approvals --}}
@can('access approvals')
@php $pendingCount = \App\Models\Approval::where('status','pending')->whereHas('steps', fn($q) => $q->where('status','pending')->whereIn('approver_role', auth()->user()->getRoleNames()->toArray()))->count(); @endphp
<a href="{{ route('approvals.index') }}"
   class="{{ request()->routeIs('approvals.*') || request()->routeIs('approval-policies.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span class="flex-1">Approvals</span>
    @if($pendingCount > 0)
    <span class="ph-nav-badge">{{ $pendingCount }}</span>
    @endif
</a>
@endcan

{{-- Chat --}}
<a href="{{ route('chat.index') }}"
   class="{{ request()->routeIs('chat.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
    <span class="flex-1">Chat</span>
    @php
        $chatUnread = \App\Models\ProjectMessage::whereHas('project', function($q) {
            $user = auth()->user();
            if ($user->hasRole(['admin','manager'])) return;
            $q->where('manager_id', $user->id)
              ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
        })->whereDoesntHave('reads', fn($r) => $r->where('user_id', auth()->id()))->count();
    @endphp
    @if($chatUnread > 0)
    <span class="ph-nav-badge">{{ $chatUnread > 99 ? '99+' : $chatUnread }}</span>
    @endif
</a>

{{-- Customer Requests --}}
@can('access requests')
<a href="{{ route('requests.index') }}"
   class="{{ request()->routeIs('requests.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
    </svg>
    Requests
</a>
@endcan

{{-- Campaigns --}}
@can('access campaigns')
<a href="{{ route('campaigns.index') }}"
   class="{{ request()->routeIs('campaigns.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
    </svg>
    Campaigns
</a>
@endcan

{{-- Invoices --}}
@can('access invoices')
<a href="{{ route('invoices.index') }}"
   class="{{ request()->routeIs('invoices.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
    </svg>
    Invoice
</a>
@endcan

{{-- Calendar --}}
@can('access calendar')
<a href="{{ route('calendar.index') }}"
   class="{{ request()->routeIs('calendar.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Kalender
</a>
@endcan

{{-- Global Search --}}
@can('access search')
<a href="{{ route('search.index') }}"
   class="{{ request()->routeIs('search.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    Global Search
</a>
@endcan

{{-- Templates --}}
@can('access templates')
<a href="{{ route('templates.index') }}"
   class="{{ request()->routeIs('templates.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
    </svg>
    Templates
</a>
@endcan

{{-- Workload --}}
@can('access workload')
<a href="{{ route('workload') }}"
   class="{{ request()->routeIs('workload') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Workload
</a>
@endcan

{{-- Analytics --}}
@can('access analytics')
<a href="{{ route('analytics.index') }}"
   class="{{ request()->routeIs('analytics.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    Analytics
</a>
@endcan

@endif {{-- /showTm --}}

{{-- ══ HRIS NAV ════════════════════════════════════════════════════════════ --}}
@if($showHris)

{{-- Karyawan --}}
<div class="pt-2 pb-1">
    <p class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest" style="color:var(--ph-section-label)">Karyawan</p>

    @can('access users')
    <a href="{{ route('users.index') }}"
       class="{{ request()->routeIs('users.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        Data Karyawan
    </a>
    @endcan

    {{-- Absensi --}}
    <a href="{{ route('hris.absensi.index') }}"
       class="{{ request()->routeIs('hris.absensi.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Absensi
    </a>
</div>

{{-- Pengajuan --}}
<div class="pt-2 pb-1">
    <p class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest" style="color:var(--ph-section-label)">Pengajuan</p>

    {{-- Cuti & Izin --}}
    <a href="{{ route('hris.leave.index') }}"
       class="{{ request()->routeIs('hris.leave.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Cuti & Izin
    </a>

    {{-- Lembur --}}
    <a href="{{ route('hris.overtime.index') }}"
       class="{{ request()->routeIs('hris.overtime.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Lembur
    </a>

    {{-- Reimburse --}}
    <a href="{{ route('hris.reimburse.index') }}"
       class="{{ request()->routeIs('hris.reimburse.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        Reimburse
    </a>
</div>

{{-- Penggajian & Konfigurasi — admin only --}}
@canany(['manage payroll', 'manage hris master', 'manage absensi', 'manage face enrollment'])
<div class="pt-2 pb-1">
    <p class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest" style="color:var(--ph-section-label)">Administrasi</p>

    @can('manage payroll')
    <a href="{{ route('hris.payroll.index') }}"
       class="{{ request()->routeIs('hris.payroll.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
        </svg>
        Penggajian
    </a>
    @endcan

    @can('manage absensi')
    <a href="{{ route('hris.absensi.setting') }}"
       class="{{ request()->routeIs('hris.absensi.setting') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Konfigurasi Absensi
    </a>
    @endcan

    @can('manage face enrollment')
    <a href="{{ route('hris.absensi.face-enrollment') }}"
       class="{{ request()->routeIs('hris.absensi.face-enrollment') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Pendaftaran Wajah
    </a>
    @endcan

    @can('manage hris master')
    <a href="{{ route('hris.master.index') }}"
       class="{{ request()->routeIs('hris.master.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Konfigurasi HRIS
    </a>
    @endcan
</div>
@endcanany

@endif {{-- /showHris --}}

{{-- ══ SHARED: Anggota Tim (Task Management) / tidak muncul di HRIS ═══════ --}}

{{-- User Management — hanya tampil di TM, di HRIS sudah ada sebagai "Data Karyawan" --}}
@if($showTm)
@can('access users')
<a href="{{ route('users.index') }}"
   class="{{ request()->routeIs('users.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>
    Anggota Tim
</a>
@endcan
@endif

{{-- Client Management — hanya di Task Management --}}
@if($showTm && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager')))
<a href="{{ route('clients.index') }}"
   class="{{ request()->routeIs('clients.*') ? $active : $inactive }}">
    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Clients
</a>
@endif

{{-- ══ Master Data section ═════════════════════════════════════════════════ --}}
@if(auth()->user()->can('access master data') || auth()->user()->can('manage master data') || auth()->user()->can('manage permissions') || auth()->user()->can('access approval policies'))
<div class="pt-3">
    <p class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest" style="color:var(--ph-section-label)">Master Data</p>

    @can('manage master data')
    <a href="{{ route('master.index') }}"
       class="{{ request()->routeIs('master.index') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Struktur Org.
    </a>
    <a href="{{ route('companies.index') }}"
       class="{{ request()->routeIs('companies.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        Perusahaan
    </a>
    <a href="{{ route('branches.index') }}"
       class="{{ request()->routeIs('branches.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Branch
    </a>
    <a href="{{ route('divisions.index') }}"
       class="{{ request()->routeIs('divisions.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
        </svg>
        Divisi
    </a>
    <a href="{{ route('departments.index') }}"
       class="{{ request()->routeIs('departments.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Departemen
    </a>
    <a href="{{ route('structural-levels.index') }}"
       class="{{ request()->routeIs('structural-levels.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
        </svg>
        Level Struktural
    </a>
    @endcan

    @can('access approval policies')
    <a href="{{ route('approval-policies.index') }}"
       class="{{ request()->routeIs('approval-policies.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Approval Policies
    </a>
    @endcan

    @can('manage permissions')
    <a href="{{ route('roles.index') }}"
       class="{{ request()->routeIs('roles.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Manajemen Role
    </a>
    <a href="{{ route('permissions.index') }}"
       class="{{ request()->routeIs('permissions.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        Permission Management
    </a>
    <a href="{{ route('activity-log.index') }}"
       class="{{ request()->routeIs('activity-log.*') ? $active : $inactive }}">
        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Activity Log
    </a>
    @endcan
</div>
@endif
