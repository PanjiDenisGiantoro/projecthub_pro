# Modul 03 — Projects (Proyek)

## Deskripsi
Manajemen proyek lengkap: CRUD, manajemen anggota, progress tracking, dan timesheet. Proyek adalah entitas induk untuk Tasks, Milestones, Bug Tickets, dan KB Articles.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/projects` | `projects.index` | Daftar semua proyek (grid card) |
| GET | `/projects/create` | `projects.create` | Form buat proyek baru |
| POST | `/projects` | `projects.store` | Simpan proyek baru |
| GET | `/projects/{project}` | `projects.show` | Detail proyek (7 tab) |
| GET | `/projects/{project}/edit` | `projects.edit` | Form edit proyek |
| PUT | `/projects/{project}` | `projects.update` | Update proyek |
| DELETE | `/projects/{project}` | `projects.destroy` | Soft delete proyek |
| POST | `/projects/{project}/members` | `projects.members.add` | Tambah anggota |
| DELETE | `/projects/{project}/members/{user}` | `projects.members.remove` | Hapus anggota |
| GET | `/projects/{project}/timesheet` | `projects.timesheet` | Laporan timesheet proyek |

## Model
`app/Models/Project.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `name` | string | Nama proyek |
| `description` | text | Deskripsi |
| `client_id` | FK → users | Klien pemilik proyek |
| `manager_id` | FK → users | Manager PIC |
| `status` | enum | `planning`, `active`, `on_hold`, `completed`, `cancelled` |
| `priority` | enum | `low`, `medium`, `high`, `critical` |
| `start_date` | date | Tanggal mulai |
| `end_date` | date | Tanggal selesai target |
| `budget` | decimal | Anggaran proyek |
| `progress` | integer | Persentase (0–100) |
| `deleted_at` | timestamp | Soft delete |

### Traits & Features
- `SoftDeletes` — proyek yang dihapus tidak hilang permanen
- `LogsActivity` (Spatie v5) — semua perubahan tercatat di activity log

### Relasi
- `client` → `User` (belongsTo)
- `manager` → `User` (belongsTo)
- `members` → `User` (belongsToMany via `project_members`)
- `tasks` → `Task` (hasMany)
- `milestones` → `Milestone` (hasMany)
- `tickets` → `BugTicket` (hasMany)
- `timeLogs` → `TimeLog` (hasManyThrough via tasks)
- `kbArticles` → `KbArticle` (hasMany)
- `invoices` → `Invoice` (hasMany)

## Controller
`app/Http/Controllers/Web/ProjectWebController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Daftar proyek (filter by role: customer hanya lihat proyeknya) |
| `create()` | Form buat proyek + daftar user untuk client/manager |
| `store()` | Validasi + simpan + redirect ke show |
| `show()` | Load semua relasi untuk 7 tab |
| `edit()` | Form edit |
| `update()` | Update proyek |
| `destroy()` | Soft delete |
| `addMember()` | Tambah user ke project_members |
| `removeMember()` | Hapus user dari project_members |
| `timesheet()` | Laporan time log per task dalam proyek |

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/projects/index.blade.php` | Grid card proyek dengan badge status/prioritas |
| `resources/views/projects/create.blade.php` | Form buat proyek |
| `resources/views/projects/edit.blade.php` | Form edit + range progress |
| `resources/views/projects/show.blade.php` | Detail dengan 7 tab Alpine.js |
| `resources/views/projects/timesheet.blade.php` | Tabel time log per task |

### Tab pada `projects.show`
1. **Overview** — info umum, progress bar, anggota
2. **Tasks** — daftar task dengan inline add
3. **Milestones** — list milestone + form tambah
4. **Bug Tickets** — daftar tiket + link create
5. **Knowledge Base** — artikel KB proyek
6. **Timesheet** — ringkasan jam kerja per user
7. **Activity Log** — riwayat perubahan (Spatie Activitylog)

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat semua | ✓ | ✓ | ✓ | ✓ | hanya miliknya |
| Buat / Edit / Hapus | ✓ | ✓ | — | — | — |
| Tambah anggota | ✓ | ✓ | — | — | — |
| Lihat timesheet | ✓ | ✓ | ✓ | — | — |
