# Modul 12 — Notifications

## Deskripsi
Sistem notifikasi internal (in-app) yang disimpan ke database. Notifikasi dikirim oleh berbagai modul secara otomatis melalui `NotificationService`. Tidak menggunakan WebSocket — dibaca saat request berikutnya.

## API Routes (untuk fetch notifikasi via JS)

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/api/notifications` | — | Daftar notifikasi user |
| GET | `/api/notifications/unread-count` | — | Jumlah notifikasi belum dibaca |
| PUT | `/api/notifications/{id}/read` | — | Tandai satu notifikasi dibaca |
| PUT | `/api/notifications/mark-all-read` | — | Tandai semua dibaca |

## Model
`app/Models/PhNotification.php`

> Menggunakan nama `PhNotification` dan tabel `ph_notifications` untuk menghindari konflik dengan `Illuminate\Notifications\Notification` bawaan Laravel.

| Field | Tipe | Keterangan |
|-------|------|------------|
| `user_id` | FK → users | Penerima notifikasi |
| `type` | string | Tipe notifikasi (e.g. `new_ticket`, `approved`) |
| `title` | string | Judul singkat |
| `message` | text | Isi pesan |
| `data` | json | Payload tambahan (e.g. `{ticket_id: 5}`) |
| `read_at` | datetime | Waktu dibaca (null = belum dibaca) |

### Scope
- `scopeUnread($query)` — filter notifikasi dengan `read_at IS NULL`

## Service
`app/Services/NotificationService.php`

| Method | Fungsi |
|--------|--------|
| `send($userId, $type, $title, $message, $data)` | Kirim notifikasi ke satu user |
| `notifyManagers($type, $title, $message, $data)` | Kirim ke semua user dengan role `manager` |
| `notifyByRole($role, $type, $title, $message, $data)` | Kirim ke semua user dengan role tertentu |

## Notifikasi yang Dikirim Otomatis

| Trigger | Tipe | Penerima |
|---------|------|----------|
| Tiket baru dibuat | `new_ticket` | Semua Manager |
| Tiket di-assign | `ticket_assigned` | Developer yang ditugaskan |
| SLA breach | `sla_breach` | Semua Manager |
| SLA warning (75%) | `sla_warning` | Semua Manager |
| Invoice overdue | `invoice_overdue` | Semua Manager |
| Request baru | `new_request` | Semua Marketing |
| Request disetujui | `request_approved` | Customer pembuat |
| Request ditolak | `request_rejected` | Customer pembuat |
| Invoice dikirim | `invoice_sent` | Customer penerima |

## Tampilan di Frontend
Notifikasi ditampilkan via badge counter di top bar (opsional — perlu implementasi JS fetch ke `/api/notifications/unread-count`).
