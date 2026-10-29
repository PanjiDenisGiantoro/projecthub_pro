<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
@php
    $units = $chart['units'];
    $unitCount = max(1, count($units));
    // Lebar area timeline A4 landscape setelah kolom label (pt)
    $labelWidth = 190;
    $unitWidth = round(min(28, (770 - $labelWidth) / $unitCount), 2);
    $months = collect($units)->groupBy('month', true)->map->count();
@endphp
<style>
@page { margin: 22px 26px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1f2937; }
h1 { font-size: 15px; font-weight: bold; margin: 0 0 2px; }
.sub { color: #6b7280; font-size: 8.5px; margin: 0 0 10px; }
table.gantt { border-collapse: collapse; }
table.gantt td, table.gantt th { padding: 0; border-bottom: 1px solid #e5e7eb; }
th.month { background: #f3f4f6; color: #4b5563; font-size: 7.5px; font-weight: bold; text-align: center; border-left: 1px solid #d1d5db; height: 13px; }
th.unit { background: #f9fafb; color: #6b7280; font-size: 6.5px; font-weight: normal; text-align: center; border-left: 1px solid #e5e7eb; height: 22px; line-height: 1.15; }
th.unit b { display: block; color: #111827; font-size: 7.5px; }
th.unit.weekend, td.cell.weekend { background: #f3f4f6; }
th.unit.today b { color: #2563eb; }
th.label { background: #f3f4f6; text-align: left; padding-left: 6px; font-size: 7px; color: #6b7280; text-transform: uppercase; }
td.label { padding: 3px 6px; height: 22px; border-right: 1px solid #d1d5db; vertical-align: middle; overflow: hidden; }
td.label .name { font-weight: bold; font-size: 8px; white-space: nowrap; overflow: hidden; }
td.label .meta { color: #6b7280; font-size: 6.5px; white-space: nowrap; overflow: hidden; }
td.label .warn { color: #b45309; font-weight: bold; }
td.cell { border-left: 1px solid #f1f5f9; }
td.cell.today { background: #eff6ff; }
td.group { font-weight: bold; font-size: 7.5px; padding: 3px 6px; height: 13px; text-transform: uppercase; }
td.barcell { padding: 4px 1px; vertical-align: middle; }
.bar { border-radius: 3px; color: #ffffff; font-size: 6.5px; font-weight: bold; line-height: 8px; padding: 2.5px 3px 2.5px; white-space: nowrap; overflow: hidden; }
.bar.estimated { opacity: 0.55; border: 1px dashed #ffffff; }
.bar.clip-start { border-top-left-radius: 0; border-bottom-left-radius: 0; }
.bar.clip-end { border-top-right-radius: 0; border-bottom-right-radius: 0; }
.nodate { border: 1px dashed #f59e0b; background: #fffbeb; color: #b45309; border-radius: 3px; font-size: 6.5px; font-weight: bold; line-height: 8px; padding: 2px 4px; white-space: nowrap; }
.legend { margin-top: 10px; font-size: 7.5px; color: #4b5563; }
.legend span.sw { display: inline-block; width: 9px; height: 9px; border-radius: 2px; vertical-align: middle; margin: 0 3px 0 10px; }
</style>
</head>
<body>
<h1>Gantt Chart — {{ $project->name }}</h1>
<p class="sub">Periode: {{ $ganttStart->format('d M Y') }} s/d {{ $ganttEnd->format('d M Y') }} ({{ $chart['byWeek'] ? 'per minggu' : 'per hari' }}) &nbsp;|&nbsp; Dibuat: {{ now()->format('d M Y H:i') }}</p>

@if(empty($chart['rows']))
    <p class="sub">Tidak ada sprint atau task pada periode ini.</p>
@else
<table class="gantt" style="width: {{ $labelWidth + $unitWidth * $unitCount }}pt;">
    <colgroup>
        <col style="width: {{ $labelWidth }}pt;">
        @foreach($units as $u)<col style="width: {{ $unitWidth }}pt;">@endforeach
    </colgroup>
    <thead>
        <tr>
            <th class="label" rowspan="2" style="width: {{ $labelWidth }}pt;"><div style="width: {{ $labelWidth - 8 }}pt;">Task / Sprint</div></th>
            @foreach($months as $month => $span)
                <th class="month" colspan="{{ $span }}">{{ $month }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($units as $u)
                <th class="unit {{ $u['weekend'] ? 'weekend' : '' }} {{ $u['today'] ? 'today' : '' }}" style="width: {{ $unitWidth }}pt;"><b>{{ $u['label'] }}</b>{{ $u['sub'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($chart['rows'] as $row)
            @if($row['kind'] === 'group')
                <tr>
                    <td class="group" colspan="{{ $unitCount + 1 }}" style="background: {{ $row['bg'] }}; color: {{ $row['color'] }};">
                        {{ $row['label'] }} <span style="font-weight: normal; text-transform: none;">· {{ $row['count'] }}</span>
                    </td>
                </tr>
                @continue
            @endif
            @php $b = $row['bar']; @endphp
            <tr>
                <td class="label" style="width: {{ $labelWidth }}pt; max-width: {{ $labelWidth }}pt;">
                    <div class="name" style="width: {{ $labelWidth - 12 }}pt;">{{ $row['name'] }}</div>
                    <div class="meta" style="width: {{ $labelWidth - 12 }}pt;">
                        {{ $row['sub'] }} · {{ $row['pic'] }} · {{ $row['status'] }}
                        @if($row['note']) · <span class="warn">{{ $row['note'] }}</span>@endif
                    </div>
                </td>
                @if(!$b)
                    <td class="barcell" colspan="{{ $unitCount }}"><span class="nodate">Belum ada tanggal</span></td>
                @else
                    @for($i = 0; $i < $b['from']; $i++)
                        <td class="cell {{ $units[$i]['weekend'] ? 'weekend' : '' }} {{ $units[$i]['today'] ? 'today' : '' }}"></td>
                    @endfor
                    <td class="barcell" colspan="{{ $b['to'] - $b['from'] + 1 }}">
                        <div class="bar {{ $row['estimated'] ? 'estimated' : '' }} {{ $b['clipStart'] ? 'clip-start' : '' }} {{ $b['clipEnd'] ? 'clip-end' : '' }}"
                             style="background: {{ $row['color'] }};">@if($b['clipStart'])&laquo; @endif{{ $row['name'] }}@if($b['clipEnd']) &raquo;@endif</div>
                    </td>
                    @for($i = $b['to'] + 1; $i < $unitCount; $i++)
                        <td class="cell {{ $units[$i]['weekend'] ? 'weekend' : '' }} {{ $units[$i]['today'] ? 'today' : '' }}"></td>
                    @endfor
                @endif
            </tr>
        @endforeach
    </tbody>
</table>

<div class="legend">
    Keterangan:
    @foreach($chart['legend'] as $label => $color)
        <span class="sw" style="background: {{ $color }};"></span>{{ $label }}
    @endforeach
    <span class="sw" style="background: #6366f1; opacity: .55;"></span>Tanggal mengikuti sprint
</div>
@endif
</body>
</html>
