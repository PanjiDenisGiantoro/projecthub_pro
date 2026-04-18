# Modul 10 — Knowledge Base (KB Articles)

## Deskripsi
Repositori dokumentasi dan artikel teknis yang terhubung ke proyek tertentu. Mendukung FULLTEXT search di database. Artikel dapat dibuat dan diedit secara inline tanpa pindah halaman (Alpine.js toggle).

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/projects/{project}/kb` | `kb.index` | Daftar artikel KB dalam proyek |
| POST | `/projects/{project}/kb` | `kb.store` | Buat artikel baru |
| GET | `/projects/{project}/kb/{article}` | `kb.show` | Baca artikel + edit inline |
| PUT | `/projects/{project}/kb/{article}` | `kb.update` | Update artikel |
| DELETE | `/projects/{project}/kb/{article}` | `kb.destroy` | Hapus artikel |

## Model
`app/Models/KbArticle.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `project_id` | FK → projects | Proyek terkait |
| `author_id` | FK → users | Pembuat artikel |
| `title` | string | Judul artikel |
| `content` | longtext | Isi artikel (mendukung markdown/HTML) |
| `category` | string | Kategori (opsional) |
| `tags` | json | Array tag (opsional) |
| `is_published` | boolean | Status publikasi |
| `views` | integer | Jumlah dilihat (default 0) |

### Database Index
```sql
FULLTEXT INDEX (title, content)
```
Mendukung pencarian full-text MySQL untuk query cepat.

### Relasi
- `project` → `Project` (belongsTo)
- `author` → `User` (belongsTo)

## Controller
`app/Http/Controllers/Web/KbArticleWebController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Daftar artikel dalam proyek, support search query |
| `store()` | Buat artikel baru |
| `show()` | Tampilkan artikel + increment `views` |
| `update()` | Update artikel |
| `destroy()` | Hapus artikel |

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/kb/index.blade.php` | Daftar artikel + inline create form (Alpine toggle) |
| `resources/views/kb/show.blade.php` | Baca artikel + mode edit inline (Alpine toggle) |

### Inline Edit di `kb.show`
- Default: tampilan baca (rendered content)
- Klik tombol "Edit": toggle Alpine `editMode = true`
- Form muncul di tempat yang sama, tanpa pindah URL
- Submit: `PUT /projects/{project}/kb/{article}`

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Baca artikel | ✓ | ✓ | ✓ | — | — |
| Buat artikel | ✓ | ✓ | ✓ | — | — |
| Edit / Hapus | ✓ | ✓ | ✓ (milik sendiri) | — | — |
