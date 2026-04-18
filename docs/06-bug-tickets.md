# Modul 06 — Bug Tickets

## Deskripsi
Sistem pelacakan bug dan isu teknis dengan integrasi SLA (Service Level Agreement). Mendukung komentar, riwayat perubahan, assign ke developer, dan reopen ticket.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/tickets` | `tickets.all` | Semua tiket lintas proyek |
| GET | `/projects/{project}/tickets` | `tickets.index` | Tiket dalam proyek tertentu |
| GET | `/projects/{project}/tickets/create` | `tickets.create` | Form buat tiket |
| POST | `/projects/{project}/tickets` | `tickets.store` | Simpan tiket baru |
| GET | `/tickets/{ticket}` | `tickets.show` | Detail tiket |
| PUT | `/tickets/{ticket}/assign` | `tickets.assign` | Assign ke developer |
| PUT | `/tickets/{ticket}/status` | `tickets.status` | Update status |
| POST | `/tickets/{ticket}/comments` | `tickets.comment` | Tambah komentar |
| PUT | `/tickets/{ticket}/reopen` | `tickets.reopen` | Buka kembali tiket yang closed |

## Model
`app/Models/BugTicket.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `project_id` | FK → projects | Proyek terkait |
| `reporter_id` | FK → users | Pelapor |
| `assignee_id` | FK → users | Developer yang ditugaskan |
| `sla_policy_id` | FK → sla_policies | Kebijakan SLA yang diterapkan |
| `title` | string | Judul tiket |
| `description` | text | Deskripsi masalah |
| `type` | enum | `bug`, `issue`, `enhancement`, `security`, `performance` |
| `priority` | enum | `critical`, `high`, `medium`, `low` |
| `status` | enum | `open`, `assigned`, `in_progress`, `pending_review`, `resolved`, `closed`, `reopened` |
| `sla_due_at` | datetime | Deadline SLA |
| `sla_breached` | boolean | Apakah SLA sudah breach |
| `resolved_at` | datetime | Waktu resolved |
| `closed_at` | datetime | Waktu closed |

### Computed Attributes
- `sla_remaining_minutes` — sisa menit sebelum SLA breach
- `sla_percent_used` — persentase waktu SLA yang sudah terpakai

### Relasi
- `project`, `reporter`, `assignee`, `slaPolicy`
- `comments` → `TicketComment` (hasMany)
- `histories` → `TicketHistory` (hasMany)
- `tasks` → `Task` (hasMany)

## Controller
`app/Http/Controllers/Web/TicketWebController.php`

| Method | Fungsi |
|--------|--------|
| `allTickets()` | Semua tiket (lintas proyek), `$project = null` |
| `index()` | Tiket dalam satu proyek |
| `create()` | Form buat tiket |
| `store()` | Simpan tiket + terapkan SLA + notifikasi manager |
| `show()` | Detail + komentar + riwayat |
| `assign()` | Set assignee, ubah status ke `assigned`, notifikasi developer |
| `updateStatus()` | Update status + catat di TicketHistory |
| `addComment()` | Tambah komentar |
| `reopen()` | Buka tiket yang closed (max 7 hari setelah closed) |

## SLA Engine
`app/Services/SlaService.php`

### Default SLA (jika tidak ada SLA policy khusus)
| Prioritas | Waktu Resolusi |
|-----------|----------------|
| `critical` | 4 jam |
| `high` | 24 jam |
| `medium` | 3 hari (72 jam) |
| `low` | 7 hari (168 jam) |

### Cara Kerja
1. Saat tiket dibuat → `SlaService::applyPolicy()` dipanggil
2. Mencari `SlaPolicy` yang cocok (by project atau global)
3. Menghitung `sla_due_at = created_at + waktu_resolusi`
4. Scheduler berjalan setiap 5 menit → `sla:check` → cek semua tiket open
5. Jika `now() > sla_due_at` → `sla_breached = true`, notifikasi dikirim
6. Warning dikirim saat penggunaan SLA mencapai **75%**

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/tickets/index.blade.php` | Tabel tiket + filter status/prioritas + SLA summary cards |
| `resources/views/tickets/create.blade.php` | Form buat tiket |
| `resources/views/tickets/show.blade.php` | Detail + progress bar SLA + komentar + riwayat + assign + reopen |

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat semua tiket | ✓ | ✓ | ✓ | — | hanya miliknya |
| Buat tiket | ✓ | ✓ | ✓ | — | ✓ |
| Assign tiket | ✓ | ✓ | — | — | — |
| Update status | ✓ | ✓ | ✓ | — | — |
| Komentar | ✓ | ✓ | ✓ | — | ✓ |
| Reopen | ✓ | ✓ | — | — | ✓ |
