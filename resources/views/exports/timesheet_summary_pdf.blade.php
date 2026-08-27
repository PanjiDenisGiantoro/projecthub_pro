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
.total-row td { font-weight: bold; border-top: 2px solid #1e40af; }
</style>
</head>
<body>
<h1>Ringkasan per Developer — {{ $project->name }}</h1>
<p class="sub">Dibuat: {{ now()->format('d M Y H:i') }}</p>

<table>
    <thead>
        <tr>
            <th>Developer</th>
            <th>Email</th>
            <th>Total Jam</th>
            <th>Jumlah Log</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summary as $row)
        <tr>
            <td>{{ $row['user']->name ?? '-' }}</td>
            <td>{{ $row['user']->email ?? '-' }}</td>
            <td>{{ number_format($row['total_hours'], 2) }}</td>
            <td>{{ $row['logs_count'] }}</td>
        </tr>
        @empty
        <tr><td colspan="4">Belum ada data timesheet.</td></tr>
        @endforelse
        @if(count($summary) > 0)
        <tr class="total-row">
            <td colspan="2">Total Keseluruhan</td>
            <td>{{ number_format(collect($summary)->sum('total_hours'), 2) }}</td>
            <td>{{ collect($summary)->sum('logs_count') }}</td>
        </tr>
        @endif
    </tbody>
</table>
</body>
</html>
