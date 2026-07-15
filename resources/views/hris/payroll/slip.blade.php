<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji {{ $payroll->user->name }} — {{ \Carbon\Carbon::create($payroll->year, $payroll->month)->locale('id')->isoFormat('MMMM Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; padding: 30px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #7c3aed; padding-bottom: 12px; }
        .header h1 { font-size: 18px; font-weight: bold; color: #7c3aed; }
        .header p { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
        .info-item { }
        .info-item label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; }
        .info-item p { font-weight: 600; color: #111827; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f3f4f6; padding: 6px 10px; text-align: left; font-size: 10px; color: #6b7280; text-transform: uppercase; }
        td { padding: 5px 10px; border-bottom: 1px solid #f3f4f6; }
        .amount { text-align: right; }
        .total-row td { font-weight: bold; background: #f9fafb; }
        .net-pay { background: #ede9fe; padding: 12px 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
        .net-pay .label { font-weight: bold; font-size: 13px; }
        .net-pay .value { font-weight: 900; font-size: 18px; color: #7c3aed; }
        .footer { margin-top: 24px; text-align: right; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $payroll->user->company->name ?? 'Flovig' }}</h1>
        <p>SLIP GAJI — {{ strtoupper(\Carbon\Carbon::create($payroll->year, $payroll->month)->locale('id')->isoFormat('MMMM Y')) }}</p>
    </div>

    <div class="info-grid">
        <div class="info-item"><label>Nama</label><p>{{ $payroll->user->name }}</p></div>
        <div class="info-item"><label>Email</label><p>{{ $payroll->user->email }}</p></div>
        <div class="info-item"><label>Periode</label><p>{{ \Carbon\Carbon::create($payroll->year, $payroll->month)->locale('id')->isoFormat('MMMM Y') }}</p></div>
        <div class="info-item"><label>Status</label><p>{{ ucfirst($payroll->status) }}</p></div>
    </div>

    <p style="font-size:10px;font-weight:bold;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Kehadiran</p>
    <table style="margin-bottom:14px;">
        <thead><tr>
            <th>Hari Kerja</th><th>Hadir</th><th>Cuti (Dibayar)</th><th>Alpha</th>
        </tr></thead>
        <tbody><tr>
            <td>{{ $payroll->hari_kerja }} hari</td>
            <td>{{ $payroll->hari_hadir }} hari</td>
            <td>{{ $payroll->hari_cuti }} hari</td>
            <td style="color:#dc2626;font-weight:bold;">{{ $payroll->hari_alpha }} hari</td>
        </tr></tbody>
    </table>

    <p style="font-size:10px;font-weight:bold;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Pendapatan</p>
    <table>
        <tbody>
            @foreach([
                ['Gaji Pokok', $payroll->gaji_pokok],
                ['Tunjangan Transport', $payroll->tunjangan_transport],
                ['Tunjangan Makan', $payroll->tunjangan_makan],
                ['Tunjangan Jabatan', $payroll->tunjangan_jabatan],
                ['Tunjangan Lainnya', $payroll->tunjangan_lainnya],
                ['Lembur', $payroll->lembur],
                ['Reimburse', $payroll->reimburse],
            ] as [$label, $val])
            @if($val > 0)
            <tr><td>{{ $label }}</td><td class="amount">Rp {{ number_format($val, 0, ',', '.') }}</td></tr>
            @endif
            @endforeach
            <tr class="total-row"><td>Total Bruto</td><td class="amount">Rp {{ number_format($payroll->penghasilan_bruto, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <p style="font-size:10px;font-weight:bold;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;margin-top:10px;">Potongan</p>
    <table>
        <tbody>
            @foreach([
                ['BPJS Kesehatan (1%)', $payroll->potongan_bpjs_kes],
                ['BPJS Ketenagakerjaan', $payroll->potongan_bpjs_tk],
                ['PPh 21', $payroll->potongan_pph21],
                ['Alpha (' . $payroll->hari_alpha . ' hari)', $payroll->potongan_alpha],
                ['Potongan Lainnya', $payroll->potongan_lainnya],
            ] as [$label, $val])
            @if($val > 0)
            <tr><td>{{ $label }}</td><td class="amount">- Rp {{ number_format($val, 0, ',', '.') }}</td></tr>
            @endif
            @endforeach
            <tr class="total-row"><td>Total Potongan</td><td class="amount">- Rp {{ number_format($payroll->total_potongan, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <div class="net-pay">
        <span class="label">Gaji Bersih</span>
        <span class="value">Rp {{ number_format($payroll->gaji_bersih, 0, ',', '.') }}</span>
    </div>

    <div class="footer">
        Dicetak pada {{ now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} &nbsp;|&nbsp; Slip gaji ini dihasilkan secara otomatis oleh sistem.
    </div>
</body>
</html>
