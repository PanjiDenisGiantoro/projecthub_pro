<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #374151; background: #f9fafb; margin: 0; padding: 20px; }
.card { background: white; border-radius: 12px; padding: 32px; max-width: 520px; margin: 0 auto; border: 1px solid #e5e7eb; }
.info { background: #F3F4F6; border-radius: 8px; padding: 16px; margin: 16px 0; }
.info p { margin: 0 0 6px; font-size: 14px; }
.info span { color: #6B7280; }
.info strong { color: #111827; }
.note { display: inline-block; margin-top: 8px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #FEF3C7; color: #D97706; }
</style></head>
<body>
<div class="card">
    <div style="color:#2563EB;font-weight:700;font-size:18px;margin-bottom:16px;">Flovig</div>
    <h2 style="margin:0 0 8px;font-size:20px;color:#111827;">{{ $eventLabel }} Tercatat</h2>
    <p style="color:#6B7280;margin-bottom:8px;">Halo {{ $name }}, {{ strtolower($eventLabel) }} Anda berhasil tercatat:</p>

    <div class="info">
        <p><span>Tanggal:</span> <strong>{{ $date }}</strong></p>
        <p><span>Jam:</span> <strong>{{ $time }}</strong></p>
        @if($note)
        <span class="note">{{ $note }}</span>
        @endif
    </div>

    <p style="font-size:12px;color:#9CA3AF;margin-top:16px;">Email ini otomatis dikirim karena notifikasi email diaktifkan di profil Anda. Matikan lewat menu Profil kalau tidak ingin menerima email ini lagi.</p>
</div>
</body>
</html>
