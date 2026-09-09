@extends('layouts.app')
@section('title', 'HRIS Dashboard')
@section('page-title', 'HRIS Dashboard')

@section('content')
<div class="space-y-6 pt-5">

    {{-- Greeting --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                Welcome, {{ auth()->user()->name }} 👋
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ \Carbon\Carbon::now()->format('l, F j, Y') }}
                &mdash; Human Resource Information System
            </p>
        </div>
        @can('access users')
        <a href="{{ route('users.index') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shrink-0 transition-all hover:-translate-y-0.5"
           style="background:var(--hris-gradient);box-shadow:0 4px 14px rgba(124,58,237,0.35)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Employees
        </a>
        @endcan
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {{-- Total Karyawan --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalKaryawan) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Total Employees</p>
        </div>

        {{-- Total Departemen --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalDept) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Departments</p>
        </div>

        {{-- Absensi Hari Ini --}}
        <a href="{{ route('hris.absensi.rekap') }}" class="block bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-100">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($hadirHariIni) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Present Today</p>
        </a>

        {{-- Cuti Pending --}}
        <a href="{{ route('hris.leave.index') }}" class="block bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-100">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($cutiPending) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Leave Requests</p>
        </a>
    </div>

    {{-- ── Row: Tren Kehadiran + Status Kehadiran Bulan Ini ──────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Tren Kehadiran 14 Hari --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 pt-5 mb-1 flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Attendance Trends</h3>
                        <p class="text-sm text-slate-400 mt-0.5">Last 14 days</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>Present</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>Permit</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-sky-400 inline-block"></span>Sick</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>Absent</span>
                </div>
            </div>
            <div class="px-6 pb-6" style="height:260px">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>

        {{-- Status Kehadiran Bulan Ini --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 pt-5">
                <div class="w-9 h-9 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Attendance Status</h3>
                    <p class="text-sm text-slate-400 mt-0.5">{{ \Carbon\Carbon::now()->format('F Y') }}</p>
                </div>
            </div>
            <div class="px-6 pb-2" style="height:165px;position:relative">
                <canvas id="attendanceStatusChart"></canvas>
            </div>
            <div class="px-6 pb-6 space-y-2.5" id="attendanceStatusLegend">
                @foreach([
                    ['#059669','Present', $attendance_status_month['hadir'] ?? 0],
                    ['#d97706','Permit',  $attendance_status_month['izin'] ?? 0],
                    ['#0ea5e9','Sick',    $attendance_status_month['sakit'] ?? 0],
                    ['#ef4444','Absent',  $attendance_status_month['alpha'] ?? 0],
                    ['#7c3aed','Leave',   $attendance_status_month['cuti'] ?? 0],
                ] as [$col,$label,$val])
                <div class="flex items-center gap-2 text-[13px]">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $col }}"></span>
                    <span class="text-slate-600 flex-1">{{ $label }}</span>
                    <span class="font-bold text-slate-900 tabular-nums">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── Row: Cuti & Izin · Lembur · Reimburse ──────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Cuti & Izin --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 pt-5">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Leave &amp; Permits</h3>
                    <p class="text-sm text-slate-400 mt-0.5">Application status this year</p>
                </div>
            </div>
            <div class="px-6 pb-2" style="height:150px;position:relative">
                <canvas id="leaveStatusChart"></canvas>
            </div>
            <div class="px-6 pb-6 space-y-2.5">
                @foreach([
                    ['#d97706','Pending',   $leave_status_year['pending'] ?? 0],
                    ['#059669','Approved',  $leave_status_year['approved'] ?? 0],
                    ['#ef4444','Rejected',  $leave_status_year['rejected'] ?? 0],
                    ['#94a3b8','Cancelled', $leave_status_year['cancelled'] ?? 0],
                ] as [$col,$label,$val])
                <div class="flex items-center gap-2 text-[13px]">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $col }}"></span>
                    <span class="text-slate-600 flex-1">{{ $label }}</span>
                    <span class="font-bold text-slate-900 tabular-nums">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Lembur --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 pt-5">
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Overtime</h3>
                    <p class="text-sm text-slate-400 mt-0.5">Approved hours · 6 months</p>
                </div>
            </div>
            <div class="px-6 pb-6" style="height:200px">
                <canvas id="overtimeChart"></canvas>
            </div>
        </div>

        {{-- Reimburse --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 pt-5">
                <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Reimbursement</h3>
                    <p class="text-sm text-slate-400 mt-0.5">By category · this month</p>
                </div>
            </div>
            <div class="px-6 pb-6" style="height:200px">
                <canvas id="reimburseChart"></canvas>
            </div>
        </div>

    </div>

    {{-- Modul Core HRIS --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Core HRIS Modules</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @php
            $modules = [
                ['label' => 'Attendance',       'route' => 'hris.absensi.index',  'perm' => null,             'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                                              'color' => 'emerald'],
                ['label' => 'Payroll',          'route' => 'hris.payroll.index',  'perm' => 'view payroll',   'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'blue'],
                ['label' => 'Payroll Settings', 'route' => 'hris.payroll.setting', 'perm' => 'update payroll', 'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',                                                        'color' => 'orange'],
                ['label' => 'Leave & Permits',   'route' => 'hris.leave.index',    'perm' => null,             'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',                                                                 'color' => 'amber'],
                ['label' => 'Overtime',         'route' => 'hris.overtime.index', 'perm' => null,             'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                                              'color' => 'red'],
                ['label' => 'Reimbursement',    'route' => 'hris.reimburse.index','perm' => null,             'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',         'color' => 'teal'],
            ];
            $colorMap = [
                'emerald' => ['bg' => 'bg-emerald-50', 'icon' => 'text-emerald-500', 'border' => 'border-emerald-100'],
                'blue'    => ['bg' => 'bg-blue-50',    'icon' => 'text-blue-500',    'border' => 'border-blue-100'],
                'orange'  => ['bg' => 'bg-orange-50',  'icon' => 'text-orange-500',  'border' => 'border-orange-100'],
                'amber'   => ['bg' => 'bg-amber-50',   'icon' => 'text-amber-500',   'border' => 'border-amber-100'],
                'red'     => ['bg' => 'bg-red-50',     'icon' => 'text-red-500',     'border' => 'border-red-100'],
                'teal'    => ['bg' => 'bg-teal-50',    'icon' => 'text-teal-500',    'border' => 'border-teal-100'],
            ];
            @endphp

            @foreach($modules as $mod)
            @continue($mod['perm'] && !auth()->user()->can($mod['perm']))
            @php $c = $colorMap[$mod['color']]; @endphp
            <a href="{{ route($mod['route']) }}" class="group bg-white rounded-2xl border {{ $c['border'] }} p-4 flex flex-col items-center gap-2 text-center hover:shadow-md hover:-translate-y-0.5 transition-all">
                <div class="w-11 h-11 rounded-xl {{ $c['bg'] }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $c['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $mod['icon'] }}"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-gray-700 leading-tight group-hover:text-gray-900">{{ $mod['label'] }}</p>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Menu Pendukung --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Supporting Modules</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            {{-- Data Karyawan --}}
            @can('access users')
            <a href="{{ route('users.index') }}"
               class="group bg-white rounded-2xl border border-blue-100 p-5 flex items-center gap-4 hover:border-blue-300 hover:shadow-md transition-all">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center shrink-0 group-hover:bg-blue-200 transition-colors">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">Employees</p>
                    <p class="text-xs text-gray-500 mt-0.5">Manage employee data, roles, and permissions</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-400 ml-auto shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endcan

            {{-- Struktur Organisasi --}}
            @can('access master data')
            <a href="{{ route('master.index') }}"
               class="group bg-white rounded-2xl border border-blue-100 p-5 flex items-center gap-4 hover:border-blue-300 hover:shadow-md transition-all">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center shrink-0 group-hover:bg-blue-200 transition-colors">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">Organization Structure</p>
                    <p class="text-xs text-gray-500 mt-0.5">Manage branches, divisions, and departments</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-400 ml-auto shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endcan

            {{-- Level Struktural --}}
            @can('access master data')
            <a href="{{ route('structural-levels.index') }}"
               class="group bg-white rounded-2xl border border-blue-100 p-5 flex items-center gap-4 hover:border-blue-300 hover:shadow-md transition-all">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center shrink-0 group-hover:bg-blue-200 transition-colors">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">Structural Levels</p>
                    <p class="text-xs text-gray-500 mt-0.5">Job positions and organizational hierarchy</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-400 ml-auto shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endcan

        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";

    // ── 1. Tren Kehadiran 14 Hari ───────────────────────────────────────────
    (function () {
        const ctx = document.getElementById('attendanceTrendChart');
        if (!ctx) return;
        const data = @json($attendance_trend);
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.map(d => d.label),
                datasets: [
                    { label: 'Present', data: data.map(d => d.hadir), backgroundColor: '#059669', borderRadius: 3 },
                    { label: 'Permit',  data: data.map(d => d.izin),  backgroundColor: '#fbbf24', borderRadius: 3 },
                    { label: 'Sick',    data: data.map(d => d.sakit), backgroundColor: '#38bdf8', borderRadius: 3 },
                    { label: 'Absent',  data: data.map(d => d.alpha), backgroundColor: '#f87171', borderRadius: 3 },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.9)', cornerRadius: 8, padding: 12,
                        titleFont: { size: 12, weight: '600' }, bodyFont: { size: 12 },
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8', precision: 0 } }
                }
            }
        });
    })();

    // ── 2. Status Kehadiran Bulan Ini ───────────────────────────────────────
    (function () {
        const ctx = document.getElementById('attendanceStatusChart');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Permit', 'Sick', 'Absent', 'Leave'],
                datasets: [{
                    data: [
                        {{ $attendance_status_month['hadir'] ?? 0 }},
                        {{ $attendance_status_month['izin'] ?? 0 }},
                        {{ $attendance_status_month['sakit'] ?? 0 }},
                        {{ $attendance_status_month['alpha'] ?? 0 }},
                        {{ $attendance_status_month['cuti'] ?? 0 }},
                    ],
                    backgroundColor: ['#059669', '#d97706', '#0ea5e9', '#ef4444', '#7c3aed'],
                    borderWidth: 0,
                    cutout: '70%',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', cornerRadius: 8, callbacks: { label: c => ` ${c.label}: ${c.parsed}` } }
                }
            }
        });
    })();

    // ── 3. Cuti & Izin — Status Tahun Ini ───────────────────────────────────
    (function () {
        const ctx = document.getElementById('leaveStatusChart');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected', 'Cancelled'],
                datasets: [{
                    data: [
                        {{ $leave_status_year['pending'] ?? 0 }},
                        {{ $leave_status_year['approved'] ?? 0 }},
                        {{ $leave_status_year['rejected'] ?? 0 }},
                        {{ $leave_status_year['cancelled'] ?? 0 }},
                    ],
                    backgroundColor: ['#d97706', '#059669', '#ef4444', '#94a3b8'],
                    borderWidth: 0,
                    cutout: '65%',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', cornerRadius: 8, callbacks: { label: c => ` ${c.label}: ${c.parsed}` } }
                }
            }
        });
    })();

    // ── 4. Lembur — Jam per Bulan ────────────────────────────────────────────
    (function () {
        const ctx = document.getElementById('overtimeChart');
        if (!ctx) return;
        const data = @json($overtime_monthly);
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.map(d => d.month),
                datasets: [{
                    label: 'Overtime Hours',
                    data: data.map(d => d.hours),
                    backgroundColor: '#f87171',
                    borderRadius: 6,
                    maxBarThickness: 28,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.9)', cornerRadius: 8, padding: 12,
                        callbacks: { label: c => ` ${c.parsed.y} hours` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } }
                }
            }
        });
    })();

    // ── 5. Reimburse per Kategori ────────────────────────────────────────────
    (function () {
        const ctx = document.getElementById('reimburseChart');
        if (!ctx) return;
        const data = @json($reimburse_by_category);
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.map(d => d.label),
                datasets: [{
                    label: 'Total',
                    data: data.map(d => d.total),
                    backgroundColor: '#14b8a6',
                    borderRadius: 6,
                    maxBarThickness: 22,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.9)', cornerRadius: 8, padding: 12,
                        callbacks: { label: c => ` Rp ${c.parsed.x.toLocaleString('id-ID')}` }
                    }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', callback: v => 'Rp ' + (v / 1000) + 'k' } },
                    y: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } }
                }
            }
        });
    })();
});
</script>
@endpush
@endsection
