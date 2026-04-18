# Modul 01 — Autentikasi

## Deskripsi
Menangani login dan logout berbasis session (web). Tidak menggunakan SPA — sepenuhnya server-side dengan Laravel Auth facade.

## Teknologi
- `Auth::attempt()` — validasi kredensial
- Laravel Session — menyimpan state login
- Redirect setelah login berdasarkan role

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/` | — | Redirect ke `/login` |
| GET | `/login` | `login` | Tampilkan form login |
| POST | `/login` | `login.post` | Proses login |
| POST | `/logout` | `logout` | Proses logout |

## Controller
`app/Http/Controllers/Web/AuthWebController.php`

| Method | Fungsi |
|--------|--------|
| `showLogin()` | Tampilkan halaman login (redirect ke dashboard jika sudah login) |
| `login()` | Validasi email+password, `Auth::attempt()`, redirect ke dashboard |
| `logout()` | `Auth::logout()`, invalidate session, redirect ke login |

## Views
- `resources/views/auth/login.blade.php` — Form login + tabel demo account

## Demo Accounts (setelah seeder)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@projecthub.dev | password |
| Manager | manager@projecthub.dev | password |
| Developer | developer@projecthub.dev | password |
| Marketing | marketing@projecthub.dev | password |
| Customer | customer@projecthub.dev | password |

## Alur
```
GET /login → showLogin()
    ↓ (form submit)
POST /login → login()
    ↓ Auth::attempt()
    ├── Gagal → back()->withErrors()
    └── Berhasil → redirect('/dashboard')

POST /logout → logout()
    └── redirect('/login')
```
