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
.summary { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; margin-bottom: 16px; }
.summary-grid { display: table; width: 100%; }
.summary-cell { display: table-cell; width: 33%; }
.summary-label { color: #6b7280; font-size: 9px; text-transform: uppercase; }
.summary-val { font-size: 15px; font-weight: bold; color: #1e40af; }
</style>
</head>
<body>
<h1>Timesheet — {{ $project->name }}</h1>
<p class="sub">Periode: {{ $start->format('d M Y') }} s/d {{ $end->format('d M Y') }} &nbsp;|&nbsp; Dibuat: {{ now()->format('d M Y H:i') }}</p>

<div class="summary">
    <div class="summary-grid">
        <div class="summary-cell">
            <div class="summary-label">Total Entri</div>
            <div class="summary-val">{{ $logs->count() }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Total Jam</div>
            <div class="summary-val">{{ round($logs->sum('minutes') / 60, 1) }} jam</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Staff Terlibat</div>
            <div class="summary-val">{{ $summary->count() }}</div>
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Staff</th>
            <th>Task</th>
            <th>Menit</th>
            <th>Jam</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
        <tr>
            <td>{{ $log->logged_at?->format('d/m/Y') }}</td>
            <td>{{ $log->user?->name }}</td>
            <td>{{ $log->task?->title }}</td>
            <td>{{ $log->minutes }}</td>
            <td>{{ round($log->minutes / 60, 2) }}</td>
            <td>{{ $log->note }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="3">Total</td>
            <td>{{ $logs->sum('minutes') }}</td>
            <td>{{ round($logs->sum('minutes') / 60, 2) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

@if($summary->count())
<div style="margin-top:24px;">
    <h2 style="font-size:13px;font-weight:bold;margin-bottom:8px;">Rekapitulasi per Staff</h2>
    <table>
        <thead>
            <tr>
                <th>Staff</th>
                <th>Total Menit</th>
                <th>Total Jam</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $userId => $data)
            <tr>
                <td>{{ $data['name'] }}</td>
                <td>{{ $data['minutes'] }}</td>
                <td>{{ round($data['minutes'] / 60, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
</body>
</html>
