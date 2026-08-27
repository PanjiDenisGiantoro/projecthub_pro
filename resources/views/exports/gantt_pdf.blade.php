<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
.sub { color: #6b7280; font-size: 10px; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; margin-top: 12px; }
th { background: #1e40af; color: white; padding: 7px 8px; text-align: left; font-size: 10px; }
td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
tr:nth-child(even) td { background: #f8fafc; }
</style>
</head>
<body>
<h1>Gantt Chart — {{ $project->name }}</h1>
<p class="sub">Periode: {{ $ganttStart->format('d M Y') }} s/d {{ $ganttEnd->format('d M Y') }} &nbsp;|&nbsp; Dibuat: {{ now()->format('d M Y H:i') }}</p>

<table>
    <thead>
        <tr>
            <th>Task</th>
            <th>Milestone</th>
            <th>Sprint</th>
            <th>Assignee</th>
            <th>Status</th>
            <th>Mulai</th>
            <th>Selesai</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ganttTasks as $t)
        @php
            $barStart = $t->start_date ?? $t->due_date ?? $t->sprint?->start_date;
            $barEnd   = $t->due_date   ?? $t->start_date ?? $t->sprint?->end_date;
        @endphp
        <tr>
            <td>{{ $t->title }}</td>
            <td>{{ $t->milestone?->title ?? '—' }}</td>
            <td>{{ $t->sprint?->name ?? 'Backlog' }}</td>
            <td>{{ $t->assignee?->name ?? '—' }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $t->status)) }}</td>
            <td>{{ $barStart?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $barEnd?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="7">Tidak ada task untuk ditampilkan.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
