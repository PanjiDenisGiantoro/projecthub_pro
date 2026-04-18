# Modul 04 — Milestones

## Deskripsi
Penanda pencapaian dalam proyek. Milestone tidak memiliki halaman terpisah — dikelola langsung dari tab Milestones di `projects.show`.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| POST | `/projects/{project}/milestones` | `milestones.store` | Buat milestone baru |
| PUT | `/projects/{project}/milestones/{milestone}` | `milestones.update` | Update milestone |
| DELETE | `/projects/{project}/milestones/{milestone}` | `milestones.destroy` | Hapus milestone |

> Tidak ada route index/show tersendiri — milestone ditampilkan dalam tab proyek.

## Model
`app/Models/Milestone.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `project_id` | FK → projects | Proyek induk |
| `title` | string | Nama milestone |
| `description` | text | Deskripsi (opsional) |
| `due_date` | date | Target selesai |
| `status` | enum | `pending`, `in_progress`, `completed` |

### Relasi
- `project` → `Project` (belongsTo)
- `tasks` → `Task` (hasMany)

## Controller
`app/Http/Controllers/Web/MilestoneWebController.php`

| Method | Fungsi |
|--------|--------|
| `store()` | Buat milestone baru dalam proyek |
| `update()` | Update title, due_date, status |
| `destroy()` | Hapus milestone |

## UI
Milestone ditampilkan sebagai list card di tab **Milestones** pada halaman `projects.show`. Form tambah milestone menggunakan inline form dengan Alpine.js toggle.

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat | ✓ | ✓ | ✓ | — | ✓ |
| Buat / Edit / Hapus | ✓ | ✓ | — | — | — |
