# Modul 07 — Customer Requests

## Deskripsi
Permintaan layanan dari customer yang melalui rantai persetujuan tiga tahap: Customer → Marketing (review) → Manager (approve/reject). Setelah disetujui, request dapat dikonversi menjadi proyek atau task.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/requests` | `requests.index` | Daftar semua request |
| GET | `/requests/create` | `requests.create` | Form buat request (customer only) |
| POST | `/requests` | `requests.store` | Simpan request baru |
| GET | `/requests/{request}` | `requests.show` | Detail + approval actions |
| PUT | `/requests/{request}/review` | `requests.review` | Marketing: ubah ke `under_review` |
| PUT | `/requests/{request}/approve` | `requests.approve` | Manager: setujui request |
| PUT | `/requests/{request}/reject` | `requests.reject` | Manager/Marketing: tolak request |

## Model
`app/Models/CustomerRequest.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `customer_id` | FK → users | Customer pembuat request |
| `project_id` | FK → projects | Proyek terkait (nullable) |
| `title` | string | Judul permintaan |
| `description` | text | Detail permintaan |
| `type` | enum | `new_feature`, `bug_fix`, `support`, `consultation`, `other` |
| `priority` | enum | `low`, `medium`, `high`, `urgent` |
| `status` | enum | `submitted`, `under_review`, `approved`, `rejected`, `in_progress`, `completed` |
| `budget_estimate` | decimal | Estimasi budget (opsional) |
| `reviewed_by` | FK → users | Marketing yang mereview |
| `reviewed_at` | datetime | Waktu review |
| `approved_by` | FK → users | Manager yang menyetujui |
| `approved_at` | datetime | Waktu approve |
| `rejection_reason` | text | Alasan penolakan |

### Trait
- `LogsActivity` (Spatie v5) — semua perubahan status tercatat

## Controller
`app/Http/Controllers/Web/RequestWebController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Daftar request (filter by role) |
| `create()` | Form buat request |
| `store()` | Simpan request, notifikasi marketing |
| `show()` | Detail + visualisasi approval chain |
| `review()` | Marketing: set `under_review`, set `reviewed_by` + `reviewed_at` |
| `approve()` | Manager: set `approved`, set `approved_by` + `approved_at`, notifikasi customer |
| `reject()` | Manager/Marketing: set `rejected`, simpan `rejection_reason`, notifikasi customer |

## Alur Approval Chain

```
Customer membuat request
        ↓
   Status: submitted
        ↓
Marketing klik "Review"
        ↓
   Status: under_review
   reviewed_by = marketing user
        ↓
   Manager klik "Approve" atau "Reject"
   ↙                    ↘
approved               rejected
approved_by = manager  rejection_reason = ...
approved_at = now()    Notifikasi ke customer
Notifikasi ke customer
```

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/requests/index.blade.php` | Tabel request + filter status |
| `resources/views/requests/create.blade.php` | Form buat request (customer only) |
| `resources/views/requests/show.blade.php` | Detail + visualisasi step approval + tombol aksi |

### Visualisasi Approval di `show.blade.php`
Menampilkan 3 langkah dengan indikator:
1. **Submitted** — selalu completed
2. **Under Review** — completed jika status >= under_review
3. **Approved / Rejected** — completed jika status = approved/rejected

## Notifikasi Otomatis

| Event | Penerima | Pesan |
|-------|----------|-------|
| Request baru dibuat | Semua Marketing | "Request baru dari {customer}" |
| Status `under_review` | Manager | "Request siap direview" |
| Approved | Customer | "Request Anda disetujui" |
| Rejected | Customer | "Request Anda ditolak: {reason}" |

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat semua | ✓ | ✓ | — | ✓ | hanya miliknya |
| Buat request | — | — | — | — | ✓ |
| Review | ✓ | ✓ | — | ✓ | — |
| Approve / Reject | ✓ | ✓ | — | — | — |
