@php
    $periodLabel = \Carbon\Carbon::create($year, $month)->locale('id')->isoFormat('MMMM Y');
    $forPdf = $forPdf ?? false;
@endphp
@if($forPdf)
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Shift {{ $periodLabel }}</title>
    <style>
        @page { margin: 20px 18px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #1f2937; }
        .header { margin-bottom: 10px; border-bottom: 2px solid #7c3aed; padding-bottom: 6px; }
        .header h1 { font-size: 14px; color: #7c3aed; margin: 0; }
        .header p { font-size: 9px; color: #6b7280; margin: 2px 0 0; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #e5e7eb; text-align: center; padding: 2px 0; }
        table.grid th { background: #f5f3ff; font-size: 7px; }
        table.grid th.name, table.grid td.name { text-align: left; padding: 2px 4px; width: 110px; }
        table.grid td { font-size: 7px; font-weight: bold; }
        .weekend { color: #ef4444; }
        .legend { margin-top: 10px; font-size: 8px; color: #4b5563; }
        .legend span { margin-right: 12px; }
        .footer { margin-top: 8px; font-size: 7px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyName }}</h1>
        <p>JADWAL SHIFT BULANAN — {{ strtoupper($periodLabel) }}</p>
    </div>
@endif

<table class="grid">
    @unless($forPdf)
    <tr><th colspan="{{ $daysInMonth + 1 }}" style="font-weight:bold;font-size:14px;text-align:left">{{ $companyName }} — Jadwal Shift {{ $periodLabel }}</th></tr>
    <tr><td colspan="{{ $daysInMonth + 1 }}"></td></tr>
    @endunless
    <thead>
        <tr>
            <th class="name" style="font-weight:bold;background-color:#f5f3ff;width:110px">Karyawan</th>
            @for($d = 1; $d <= $daysInMonth; $d++)
            @php $colDate = $start->copy()->day($d); $isWeekend = in_array($colDate->dayOfWeek, [0, 6], true); $isHoliday = $holidays->has($colDate->toDateString()); @endphp
            <th class="{{ $isWeekend || $isHoliday ? 'weekend' : '' }}" style="font-weight:bold;text-align:center;background-color:{{ $isHoliday ? '#fee2e2' : '#f5f3ff' }};{{ $isWeekend || $isHoliday ? 'color:#dc2626' : '' }}">
                {{ $d }}<br>{{ \App\Models\Shift::DAY_LABELS[$colDate->dayOfWeek] }}
            </th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @forelse($employees as $emp)
        <tr>
            <td class="name">{{ $emp->name }}</td>
            @for($d = 1; $d <= $daysInMonth; $d++)
            @php $cell = $grid[$emp->id][$d]; @endphp
            <td style="text-align:center;font-weight:bold;color:{{ $cell['color'] }};background-color:{{ !empty($cell['holiday']) ? '#fee2e2' : ($cell['is_override'] ? '#ede9fe' : ($cell['label'] === 'Libur' ? '#f3f4f6' : '#ffffff')) }}">
                {{ !empty($cell['holiday']) ? 'LN' : ($cell['label'] === 'Libur' ? 'L' : $cell['label']) }}
            </td>
            @endfor
        </tr>
        @empty
        <tr><td colspan="{{ $daysInMonth + 1 }}">Belum ada karyawan.</td></tr>
        @endforelse
    </tbody>
</table>

@if($forPdf)
    <div class="legend">
        <strong>Keterangan:</strong>
        <span>Jam = jam masuk shift</span>
        <span>L = Libur</span>
        <span style="color:#dc2626">LN = Hari libur</span>
        <span>— = Belum diatur</span>
        <span style="color:#7c3aed">Ungu = override jadwal</span>
        @foreach($shifts as $shift)
        <span>{{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }})</span>
        @endforeach
    </div>
    @if($holidays->isNotEmpty())
    <div class="legend">
        <strong>Hari libur:</strong>
        @foreach($holidays as $holiday)
        <span>{{ $holiday->date->format('j') }} — {{ $holiday->name }}</span>
        @endforeach
    </div>
    @endif
    <div class="footer">Dicetak {{ now()->locale('id')->isoFormat('D MMMM Y HH:mm') }}</div>
</body>
</html>
@else
<table>
    <tr><td></td></tr>
    <tr><td style="font-weight:bold">Keterangan</td></tr>
    <tr><td>Jam = jam masuk shift, L = Libur, LN = Hari libur, — = Belum diatur, latar ungu = override jadwal</td></tr>
    @foreach($shifts as $shift)
    <tr><td>{{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }})</td></tr>
    @endforeach
    @if($holidays->isNotEmpty())
    <tr><td></td></tr>
    <tr><td style="font-weight:bold">Hari Libur</td></tr>
    @foreach($holidays as $holiday)
    <tr><td>{{ $holiday->date->locale('id')->isoFormat('dddd, D MMMM Y') }} — {{ $holiday->name }}</td></tr>
    @endforeach
    @endif
</table>
@endif
