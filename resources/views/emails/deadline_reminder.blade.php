<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #374151; background: #f9fafb; margin: 0; padding: 20px; }
.card { background: white; border-radius: 12px; padding: 32px; max-width: 520px; margin: 0 auto; border: 1px solid #e5e7eb; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.priority-high { background: #FEE2E2; color: #DC2626; }
.priority-medium { background: #FEF9C3; color: #D97706; }
.priority-low { background: #F0FDF4; color: #16A34A; }
.btn { display: inline-block; background: #2563EB; color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 20px; }
</style></head>
<body>
<div class="card">
    <div style="color:#2563EB;font-weight:700;font-size:18px;margin-bottom:16px;">Flovig</div>
    <h2 style="margin:0 0 8px;font-size:20px;color:#111827;">⏰ Deadline Besok!</h2>
    <p style="color:#6B7280;margin-bottom:20px;">Halo {{ $user->name }}, task berikut jatuh tempo besok:</p>

    <div style="background:#F3F4F6;border-radius:8px;padding:16px;margin-bottom:16px;">
        <p style="font-weight:600;font-size:16px;margin:0 0 8px;color:#111827;">{{ $task->title }}</p>
        <p style="margin:0;font-size:13px;color:#6B7280;">
            Proyek: {{ $task->project?->name }}<br>
            Due: {{ $task->due_date?->format('d M Y') }}<br>
            Status: {{ ucfirst($task->status) }}
        </p>
        <div style="margin-top:8px;">
            <span class="badge priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
        </div>
    </div>

    <p style="font-size:13px;color:#6B7280;">Segera selesaikan task ini sebelum deadline.</p>
</div>
</body>
</html>
