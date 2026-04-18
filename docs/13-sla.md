# Modul 13 — SLA (Service Level Agreement)

## Deskripsi
Mesin SLA untuk memastikan bug ticket diselesaikan dalam batas waktu yang disepakati berdasarkan prioritas. SLA diterapkan otomatis saat tiket dibuat dan dipantau setiap 5 menit via scheduler.

## Routes (Admin only)

| Method | URI | Keterangan |
|--------|-----|------------|
| GET | `/api/sla-policies` | Daftar semua SLA policy |
| POST | `/api/sla-policies` | Buat policy baru |
| PUT | `/api/sla-policies/{id}` | Update policy |
| DELETE | `/api/sla-policies/{id}` | Hapus policy |

> SLA Policy saat ini hanya dikelola via REST API (belum ada Blade UI tersendiri).

## Model: SlaPolicy
`app/Models/SlaPolicy.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `name` | string | Nama kebijakan |
| `priority` | enum | `critical`, `high`, `medium`, `low` |
| `resolution_hours` | integer | Target jam penyelesaian |
| `warning_threshold` | integer | Persentase (%) untuk peringatan dini |
| `project_id` | FK → projects | Spesifik proyek (null = global) |

## Model: SlaLog
`app/Models/SlaLog.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `ticket_id` | FK → bug_tickets | Tiket terkait |
| `sla_policy_id` | FK → sla_policies | Policy yang diterapkan |
| `due_at` | datetime | Deadline SLA |
| `breached_at` | datetime | Waktu breach terjadi (nullable) |
| `warning_sent_at` | datetime | Waktu warning dikirim (nullable) |

## Service
`app/Services/SlaService.php`

| Method | Fungsi |
|--------|--------|
| `applyPolicy(BugTicket $ticket)` | Cari policy yang cocok → set `sla_due_at` di tiket + buat SlaLog |
| `checkBreaches()` | Cek semua tiket open: jika `now() > sla_due_at` → set `sla_breached = true` + notifikasi |
| `sendWarnings()` | Cek tiket yang mendekati deadline (>= threshold%) → kirim warning notifikasi |

### Logika `applyPolicy()`
1. Cari `SlaPolicy` dengan `project_id = ticket->project_id` dan `priority = ticket->priority`
2. Jika tidak ada → cari policy global (`project_id IS NULL`)
3. Jika tidak ada → gunakan default hardcoded (lihat tabel di bawah)
4. Set `ticket->sla_due_at = created_at + resolution_hours`

### Default SLA (hardcoded fallback)
| Prioritas | Jam Resolusi |
|-----------|-------------|
| `critical` | 4 jam |
| `high` | 24 jam |
| `medium` | 72 jam (3 hari) |
| `low` | 168 jam (7 hari) |

## Artisan Command
`app/Console/Commands/CheckSlaBreaches.php`

```
php artisan sla:check
```

Menjalankan:
1. `SlaService::checkBreaches()` — set breach + notifikasi
2. `SlaService::sendWarnings()` — kirim warning di threshold

## Scheduler
Terdaftar di `bootstrap/app.php`:
```php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('sla:check')->everyFiveMinutes();
    $schedule->command('invoices:check-overdue')->daily();
})
```

> Untuk menjalankan scheduler di development: `php artisan schedule:run`
> Di production: tambahkan cron `* * * * * php artisan schedule:run`

## Seeder Default
4 global SLA policy dibuat otomatis:
```
critical  → 4 jam  (warning: 75%)
high      → 24 jam (warning: 75%)
medium    → 72 jam (warning: 75%)
low       → 168 jam (warning: 75%)
```
