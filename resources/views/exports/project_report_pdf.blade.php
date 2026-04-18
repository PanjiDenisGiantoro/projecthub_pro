<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
h1 { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
h2 { font-size: 13px; font-weight: bold; margin: 20px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
.sub { color: #6b7280; font-size: 10px; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-top: 6px; }
th { background: #1e40af; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
tr:nth-child(even) td { background: #f8fafc; }
.kpi-grid { display: table; width: 100%; margin-bottom: 16px; }
.kpi-cell { display: table-cell; width: 25%; padding: 8px; }
.kpi-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px; text-align: center; }
.kpi-label { font-size: 9px; color: #6b7280; text-transform: uppercase; }
.kpi-val { font-size: 16px; font-weight: bold; color: #1e40af; }
.badge-done { background: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
.badge-todo { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
.badge-prog { background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
</style>
</head>
<body>
<h1>Laporan Proyek — {{ $project->name }}</h1>
<p class="sub">
    Manager: {{ $project->manager?->name }} &nbsp;|&nbsp;
    Status: {{ ucfirst($project->status) }} &nbsp;|&nbsp;
    Periode: {{ $project->start_date?->format('d M Y') }} — {{ $project->end_date?->format('d M Y') }} &nbsp;|&nbsp;
    Dibuat: {{ now()->format('d M Y H:i') }}
</p>

{{-- KPI --}}
<div class="kpi-grid">
    <div class="kpi-cell">
        <div class="kpi-box">
            <div class="kpi-label">Total Task</div>
            <div class="kpi-val">{{ $summary['total_tasks'] }}</div>
        </div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-box">
            <div class="kpi-label">Task Selesai</div>
            <div class="kpi-val" style="color:#065f46;">{{ $summary['done_tasks'] }}</div>
        </div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-box">
            <div class="kpi-label">Task Overdue</div>
            <div class="kpi-val" style="color:#dc2626;">{{ $summary['overdue_tasks'] }}</div>
        </div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-box">
            <div class="kpi-label">Progress</div>
            <div class="kpi-val">{{ $project->progress ?? 0 }}%</div>
        </div>
    </div>
</div>

{{-- Milestones --}}
<h2>Milestones</h2>
<table>
    <thead>
        <tr><th>Milestone</th><th>Status</th><th>Mulai</th><th>Selesai</th><th>Tasks</th></tr>
    </thead>
    <tbody>
        @foreach($project->milestones as $ms)
        <tr>
            <td>{{ $ms->title }}</td>
            <td>{{ ucfirst($ms->status) }}</td>
            <td>{{ $ms->start_date?->format('d/m/Y') }}</td>
            <td>{{ $ms->due_date?->format('d/m/Y') }}</td>
            <td>{{ $ms->tasks->count() }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Tasks --}}
<h2>Daftar Task</h2>
<table>
    <thead>
        <tr><th>Judul</th><th>Assignee</th><th>Status</th><th>Prioritas</th><th>Due</th></tr>
    </thead>
    <tbody>
        @foreach($project->tasks->take(50) as $task)
        <tr>
            <td>{{ $task->title }}</td>
            <td>{{ $task->assignee?->name ?? '—' }}</td>
            <td>{{ ucfirst($task->status) }}</td>
            <td>{{ ucfirst($task->priority) }}</td>
            <td>{{ $task->due_date?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Budget --}}
@if($project->budget)
<h2>Anggaran</h2>
<table>
    <thead>
        <tr><th>Item</th><th>Nilai</th></tr>
    </thead>
    <tbody>
        <tr><td>Total Budget</td><td>Rp {{ number_format($project->budget, 0, ',', '.') }}</td></tr>
        <tr><td>Total Pengeluaran</td><td>Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</td></tr>
        <tr><td>Sisa Anggaran</td><td>Rp {{ number_format($project->budget - $summary['total_expenses'], 0, ',', '.') }}</td></tr>
    </tbody>
</table>
@endif

{{-- Risks --}}
@if($project->risks->count())
<h2>Risk Register</h2>
<table>
    <thead>
        <tr><th>Risiko</th><th>Kategori</th><th>P×I</th><th>Status</th></tr>
    </thead>
    <tbody>
        @foreach($project->risks as $risk)
        <tr>
            <td>{{ $risk->title }}</td>
            <td>{{ ucfirst($risk->category) }}</td>
            <td>{{ $risk->score() }}</td>
            <td>{{ ucfirst($risk->status) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</body>
</html>
