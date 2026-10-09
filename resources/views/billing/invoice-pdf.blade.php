@php
    $issuer = config('services.billing_issuer');
    $rp     = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');

    $status = match (true) {
        $order->isPaid()   => ['LUNAS', 'paid'],
        $order->isFailed() => ['BATAL', 'failed'],
        default            => ['BELUM DIBAYAR', 'pending'],
    };

    // DOKU mengirim channel.id seperti "VIRTUAL_ACCOUNT_BCA" / "QRIS" di HTTP Notification.
    $channel = data_get($order->raw_notification, 'channel.id') ?? data_get($order->raw_notification, 'service.id');
    $acronyms = ['BCA', 'BNI', 'BRI', 'BSI', 'BTN', 'CIMB', 'QRIS', 'OVO', 'DOKU', 'VA'];
    $channel  = $channel
        ? collect(explode('_', $channel))->map(fn ($w) => in_array($w, $acronyms, true) ? $w : ucfirst(strtolower($w)))->implode(' ')
        : null;

    // Order lama (sebelum ada PPN) tidak punya subtotal — anggap seluruh amount sebagai harga paket.
    $subtotal = $order->subtotal ?? $order->amount;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6b7280; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin-bottom: 4px; }
        .title { font-size: 24px; font-weight: bold; color: #111827; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; margin-top: 6px; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        .items th { background: #1d4ed8; color: #fff; padding: 8px 10px; text-align: left; font-size: 10px; }
        .items td { padding: 9px 10px; border-bottom: 1px solid #e5e7eb; }
        .right, .items th.right { text-align: right; }
        .totals td { padding: 5px 10px; }
        .totals .grand td { font-size: 13px; font-weight: bold; color: #111827; border-top: 2px solid #111827; padding-top: 8px; }
        .footer { margin-top: 40px; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>

<table>
    <tr>
        <td style="vertical-align: top;">
            <img src="{{ public_path('flovig_logo.png') }}" style="height: 28px;" alt="Flovig"><br>
            <div style="margin-top: 8px;">
                <strong>{{ $issuer['name'] }}</strong><br>
                @if($issuer['address']) <span class="muted">{{ $issuer['address'] }}</span><br> @endif
                @if($issuer['email']) <span class="muted">{{ $issuer['email'] }}</span><br> @endif
                @if($issuer['phone']) <span class="muted">{{ $issuer['phone'] }}</span><br> @endif
                @if($issuer['npwp']) <span class="muted">NPWP: {{ $issuer['npwp'] }}</span> @endif
            </div>
        </td>
        <td class="right" style="vertical-align: top;">
            <div class="title">INVOICE</div>
            <div class="muted">{{ $order->order_number }}</div>
            <span class="badge badge-{{ $status[1] }}">{{ $status[0] }}</span>
        </td>
    </tr>
</table>

<table style="margin: 28px 0 22px;">
    <tr>
        <td style="vertical-align: top; width: 55%;">
            <div class="label">Ditagihkan Kepada</div>
            <strong>{{ $order->company?->name ?? '—' }}</strong><br>
            @if($order->company?->address) <span class="muted">{{ $order->company->address }}</span><br> @endif
            {{ $order->user?->name }}<br>
            <span class="muted">{{ $order->user?->email }}</span>
        </td>
        <td style="vertical-align: top;" class="right">
            <div class="label">Detail</div>
            Tanggal Invoice: {{ $order->created_at->translatedFormat('d M Y') }}<br>
            @if($order->paid_at)
                Tanggal Dibayar: {{ $order->paid_at->translatedFormat('d M Y H:i') }}<br>
            @endif
            @if($channel)
                Metode Bayar: {{ $channel }}<br>
            @endif
            Pembayaran via DOKU
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Deskripsi</th>
            <th class="right" style="width: 60px;">Qty</th>
            <th class="right" style="width: 130px;">Harga</th>
            <th class="right" style="width: 130px;">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>Langganan Flovig — Paket {{ $order->package_name }}</strong><br>
                <span class="muted">Durasi {{ $order->duration_days }} hari</span>
            </td>
            <td class="right">1</td>
            <td class="right">{{ $rp($subtotal) }}</td>
            <td class="right">{{ $rp($subtotal) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals" style="width: 300px; margin-left: auto; margin-top: 8px;">
    <tr>
        <td class="muted">Subtotal</td>
        <td class="right">{{ $rp($subtotal) }}</td>
    </tr>
    @if($order->ppn_amount > 0)
        <tr>
            <td class="muted">PPN {{ \App\Models\PpnRate::formatRate($order->ppn_rate) }}</td>
            <td class="right">{{ $rp($order->ppn_amount) }}</td>
        </tr>
    @endif
    <tr class="grand">
        <td>Total</td>
        <td class="right">{{ $rp($order->amount) }}</td>
    </tr>
</table>

<div class="footer">
    Invoice ini dibuat otomatis oleh sistem dan sah tanpa tanda tangan.<br>
    Dicetak {{ now()->translatedFormat('d M Y H:i') }}
</div>

</body>
</html>
