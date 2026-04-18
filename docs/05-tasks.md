# Modul 05 — Tasks

## Deskripsi
Manajemen task dalam proyek. Mendukung time tracking (start/stop timer dan manual entry) langsung dari halaman detail task.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/projects/{project}/tasks` | `tasks.index` | Daftar task dalam proyek |
| POST | `/projects/{project}/tasks` | `tasks.store` | Buat task baru |
| GET | `/projects/{project}/tasks/{task}` | `tasks.show` | Detail task + time logs |
| PUT | `/projects/{project}/tasks/{task}` | `tasks.update` | Update task (termasuk status) |
| DELETE | `/projects/{project}/tasks/{task}` | `tasks.destroy` | Hapus task |
| POST | `/tasks/{task}/time-logs` | `tasks.timelog.store` | Tambah / mulai / stop time log |

## Model
`app/Models/Task.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `project_id` | FK → projects | Proyek induk |
| `milestone_id` | FK → milestones | Milestone (nullable) |
| `title` | string | Judul task |
| `description` | text | Deskripsi |
| `assigned_to` | FK → users | Developer yang ditugaskan |
| `status` | enum | `todo`, `in_progress`, `review`, `done` |
| `priority` | enum | `low`, `medium`, `high`, `urgent` |
| `due_date` | date | Deadline |
| `estimated_hours` | decimal | Estimasi jam kerja |
| `deleted_at` | timestamp | Soft delete |

### Method
- `totalMinutes()` — total menit dari semua time log task ini

### Relasi
- `project` → `Project` (belongsTo)
- `milestone` → `Milestone` (belongsTo)
- `assignee` → `User` (belongsTo, FK: `assigned_to`)
- `timeLogs` → `TimeLog` (hasMany)

## Controller
`app/Http/Controllers/Web/TaskWebController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Daftar task proyek, filter status/prioritas |
| `store()` | Buat task baru, assign ke developer |
| `show()` | Detail task + semua time log |
| `update()` | Update task (status, assignee, dll) |
| `destroy()` | Hapus task |
| `storeTimeLog()` | Handle 3 mode: `start`, `stop`, `manual` |

## Time Tracking

### 3 Mode Input
| Mode | Cara Kerja |
|------|------------|
| **Start** | Buat TimeLog baru dengan `started_at = now()`, `minutes = null` |
| **Stop** | Cari TimeLog aktif (minutes IS NULL), hitung selisih waktu, set `minutes` |
| **Manual** | Input jam + menit langsung, hitung total minutes |

### Model TimeLog
`app/Models/TimeLog.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `task_id` | FK → tasks | Task terkait |
| `user_id` | FK → users | User yang log |
| `started_at` | datetime | Waktu mulai |
| `minutes` | integer | Durasi (null = sedang berjalan) |
| `description` | string | Catatan (opsional) |

- `stop()` method — hitung `minutes` dari `started_at` sampai sekarang

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/tasks/index.blade.php` | Tabel task + Alpine toggle inline add form |
| `resources/views/tasks/show.blade.php` | Detail task + start/stop/manual timer + daftar time log |

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat task | ✓ | ✓ | ✓ | — | — |
| Buat / Edit | ✓ | ✓ | — | — | — |
| Update status | ✓ | ✓ | ✓ (task sendiri) | — | — |
| Time tracking | ✓ | ✓ | ✓ | — | — |
| Hapus | ✓ | ✓ | — | — | — |
