# ProjectHub Pro — Dokumentasi Teknis

## Ringkasan
ProjectHub Pro adalah aplikasi manajemen proyek berbasis web yang dibangun dengan Laravel 13 + Blade + Alpine.js + Tailwind CSS v4. Mendukung 5 role pengguna dengan fitur lengkap mulai dari manajemen proyek, bug tracking (dengan SLA), CRM sederhana, hingga invoicing.

## Stack Teknologi

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.4) |
| Frontend | Blade + Alpine.js v3 + Tailwind CSS v4 |
| Database | MySQL 8.4 |
| Auth | Laravel Sanctum (API) + Session Auth (Web) |
| RBAC | Spatie Laravel Permission v7 (guard: web) |
| Activity Log | Spatie Laravel Activitylog v5 |
| PDF | barryvdh/laravel-dompdf |
| Build Tool | Vite |
| Server (Dev) | ServBay (PHP 8.4 + MySQL 8.4) |

## Environment
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=projecthub_pro
DB_USERNAME=root
DB_PASSWORD=ServBay.dev
```

## Instalasi

```bash
# 1. Install dependencies
/c/ServBay/packages/php/current/php.exe /c/laragon/bin/composer/composer.phar install --no-scripts

# 2. Generate app key
php artisan key:generate

# 3. Migrasi + Seeder
php artisan migrate --seed

# 4. Build assets
npm install && npm run build

# 5. Jalankan server
php artisan serve
```

## Struktur Modul

| No | File | Modul |
|----|------|-------|
| 01 | [01-auth.md](01-auth.md) | Autentikasi (Login/Logout) |
| 02 | [02-dashboard.md](02-dashboard.md) | Dashboard (role-based) + Workload |
| 03 | [03-projects.md](03-projects.md) | Projects (Proyek) |
| 04 | [04-milestones.md](04-milestones.md) | Milestones |
| 05 | [05-tasks.md](05-tasks.md) | Tasks + Time Tracking |
| 06 | [06-bug-tickets.md](06-bug-tickets.md) | Bug Tickets + SLA |
| 07 | [07-customer-requests.md](07-customer-requests.md) | Customer Requests (Approval Chain) |
| 08 | [08-campaigns.md](08-campaigns.md) | Campaigns & Leads |
| 09 | [09-invoices.md](09-invoices.md) | Invoices + PDF |
| 10 | [10-knowledge-base.md](10-knowledge-base.md) | Knowledge Base (KB Articles) |
| 11 | [11-users.md](11-users.md) | User Management |
| 12 | [12-notifications.md](12-notifications.md) | Notifications (In-App) |
| 13 | [13-sla.md](13-sla.md) | SLA Engine + Scheduler |

## Roles & Akses Global

| Role | Deskripsi Singkat |
|------|------------------|
| `admin` | Akses penuh ke semua fitur |
| `manager` | Kelola proyek, setujui request, lihat laporan & revenue |
| `developer` | Kelola task, bug ticket, time tracking |
| `marketing` | Kelola campaign & lead, review customer request |
| `customer` | Submit request, lihat proyek & invoice miliknya |

## Default Users (setelah seeder)

| Email | Password | Role |
|-------|----------|------|
| admin@projecthub.dev | password | admin |
| manager@projecthub.dev | password | manager |
| developer@projecthub.dev | password | developer |
| marketing@projecthub.dev | password | marketing |
| customer@projecthub.dev | password | customer |

## Struktur Direktori Penting

```
app/
├── Console/Commands/
│   ├── CheckSlaBreaches.php       # artisan sla:check
│   └── CheckOverdueInvoices.php   # artisan invoices:check-overdue
├── Http/Controllers/Web/          # Blade web controllers
├── Models/                        # Eloquent models
└── Services/
    ├── NotificationService.php    # In-app notification
    └── SlaService.php             # SLA engine

resources/views/
├── auth/           # Login
├── campaigns/      # Campaigns & leads
├── dashboard/      # Role-based dashboards
├── invoices/       # Invoice + PDF template
├── kb/             # Knowledge base
├── layouts/        # app.blade.php + sidebar-nav.blade.php
├── projects/       # Projects (7-tab show)
├── requests/       # Customer requests
├── tasks/          # Tasks + time tracker
├── tickets/        # Bug tickets
├── users/          # User management
└── workload.blade.php

docs/               # Dokumentasi ini
```

## API REST
Selain web routes, tersedia REST API lengkap di `/api/*` dengan autentikasi Laravel Sanctum (Bearer token). Lihat `routes/api.php` untuk daftar lengkap endpoint.
