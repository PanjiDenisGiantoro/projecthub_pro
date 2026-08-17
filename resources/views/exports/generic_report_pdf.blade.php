<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
h1 { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
.sub { color: #6b7280; font-size: 10px; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-top: 6px; }
th { background: #1e40af; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
tr:nth-child(even) td { background: #f8fafc; }
</style>
</head>
<body>
<h1>Laporan {{ $title }}</h1>
<p class="sub">Dibuat: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp; Total baris: {{ $rows->count() }}</p>

<table>
    <thead>
        <tr>
            @foreach($columns as $label)
            <th>{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            @foreach(array_keys($columns) as $field)
            <td>{{ $row[$field] ?? '-' }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
