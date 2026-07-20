<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #374151; background: #f9fafb; margin: 0; padding: 20px; }
.card { background: white; border-radius: 12px; padding: 32px; max-width: 520px; margin: 0 auto; border: 1px solid #e5e7eb; }
.creds { background: #F3F4F6; border-radius: 8px; padding: 16px; margin: 16px 0; }
.creds p { margin: 0 0 6px; font-size: 14px; }
.creds span { color: #6B7280; }
.creds strong { color: #111827; font-family: 'Courier New', monospace; }
.btn { display: inline-block; background: #7C3AED; color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 20px; }
</style></head>
<body>
<div class="card">
    <div style="color:#7C3AED;font-weight:700;font-size:18px;margin-bottom:16px;">Flovig</div>
    <h2 style="margin:0 0 8px;font-size:20px;color:#111827;">Akun Anda Sudah Siap</h2>
    <p style="color:#6B7280;margin-bottom:8px;">Halo {{ $name }}, terima kasih sudah mendaftar. Berikut detail login akun Anda:</p>

    <div class="creds">
        <p><span>Email:</span> <strong>{{ $email }}</strong></p>
        <p><span>Password:</span> <strong>{{ $password }}</strong></p>
    </div>

    <p style="font-size:13px;color:#6B7280;">Simpan informasi ini baik-baik dan segera ganti password setelah login pertama Anda. Jangan bagikan email ini kepada siapa pun.</p>

    <p style="font-size:13px;color:#6B7280;margin-top:20px;">Satu langkah lagi: verifikasi email Anda supaya akun aktif sepenuhnya.</p>
    <a href="{{ $verificationUrl }}" class="btn">Verifikasi Email Saya</a>
    <p style="font-size:12px;color:#9CA3AF;margin-top:16px;">Sudah login? <a href="{{ $loginUrl }}" style="color:#7C3AED;">Masuk ke Flovig</a></p>
</div>
</body>
</html>
