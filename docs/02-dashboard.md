# Modul 02 — Dashboard

## Deskripsi
Dashboard utama yang menampilkan ringkasan data berbeda sesuai role pengguna yang sedang login. Satu route, empat tampilan berbeda.

## Routes

| Method | URI | Name | Role |
|--------|-----|------|------|
| GET | `/dashboard` | `dashboard` | Semua role |
| GET | `/workload` | `workload` | Admin, Manager |

## Controller
`app/Http/Controllers/Web/DashboardWebController.php`

### `index()`
Mengembalikan view berbeda berdasarkan role:

| Role | View | Data yang dikirim |
|------|------|-------------------|
| admin / manager | `dashboard.manager` | stats (projects, tasks, tickets, requests, revenue), recent_projects, recent_tickets, recent_requests |
| developer | `dashboard.developer` | my_tasks, stats (todo, in_progress, done_week, hours_week) |
| marketing | `dashboard.marketing` | campaigns, stats (active_campaigns, pending_review) |
| customer | `dashboard.customer` | projects, stats (pending_requests, open_tickets, unpaid_invoices), recent_requests |

### `workload()`
Menampilkan distribusi task aktif per developer. Hanya untuk admin dan manager.
- Query: semua `User` dengan role `developer`, eager load `assignedTasks` (status: todo / in_progress)

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/dashboard/manager.blade.php` | 5 stat cards + panel proyek, tiket, request terbaru |
| `resources/views/dashboard/developer.blade.php` | 4 stat cards + tabel task saya dengan filter Alpine.js |
| `resources/views/dashboard/marketing.blade.php` | 2 stat cards + tabel kampanye |
| `resources/views/dashboard/customer.blade.php` | 3 stat cards + grid proyek + tabel request |
| `resources/views/workload.blade.php` | Progress bar workload per developer |

## Workload — Logika Kapasitas
- Kapasitas default: **8 task** per developer
- Warna progress bar:
  - 0–49%: hijau (`bg-green-500`)
  - 50–74%: kuning (`bg-yellow-400`)
  - 75–99%: oranye (`bg-orange-400`)
  - 100%+: merah (`bg-red-500`)
