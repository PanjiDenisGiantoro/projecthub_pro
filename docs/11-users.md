# Modul 11 — User Management

## Deskripsi
Manajemen pengguna sistem — hanya dapat diakses oleh Admin. Meliputi CRUD pengguna, assignment role (Spatie Laravel Permission), dan aktivasi/nonaktivasi akun.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/users` | `users.index` | Daftar semua user |
| GET | `/users/create` | `users.create` | Form buat user baru |
| POST | `/users` | `users.store` | Simpan user baru |
| GET | `/users/{user}/edit` | `users.edit` | Form edit user |
| PUT | `/users/{user}` | `users.update` | Update user |
| DELETE | `/users/{user}` | `users.destroy` | Hapus user |

## Model
`app/Models/User.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `name` | string | Nama lengkap |
| `email` | string | Email (unique) |
| `password` | string | Bcrypt hash |
| `avatar` | string | Path avatar (nullable) |
| `is_active` | boolean | Status aktif (default: true) |
| `timezone` | string | Timezone (default: Asia/Jakarta) |

### Traits
- `HasApiTokens` (Laravel Sanctum) — untuk API token auth
- `HasRoles` (Spatie Permission, guard: `web`) — manajemen role
- `HasFactory`, `Notifiable`

### Relasi
- `projects` → `Project` (hasMany via `client_id`)
- `managedProjects` → `Project` (hasMany via `manager_id`)
- `assignedTasks` → `Task` (hasMany via `assigned_to`)
- `timeLogs` → `TimeLog` (hasMany)
- `phNotifications` → `PhNotification` (hasMany)

## Controller
`app/Http/Controllers/Web/UserWebController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Daftar user + role badges |
| `create()` | Form buat user + pilih role |
| `store()` | Buat user + assign role via `syncRoles()` |
| `edit()` | Form edit user |
| `update()` | Update data + role + is_active. Password hanya diupdate jika diisi. |
| `destroy()` | Hapus user |

## Roles (Spatie Laravel Permission v7)

| Role | Guard | Deskripsi |
|------|-------|-----------|
| `admin` | web | Akses penuh ke semua fitur |
| `manager` | web | Kelola proyek, setujui request, lihat laporan |
| `developer` | web | Kelola task, bug ticket, time tracking |
| `marketing` | web | Kelola campaign, review customer request |
| `customer` | web | Buat request, lihat proyek & invoice miliknya |

> **Penting**: Guard harus `web`, bukan `sanctum`, untuk kompatibilitas dengan Laravel Session auth.

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/users/index.blade.php` | Tabel user dengan badge role + status |
| `resources/views/users/create.blade.php` | Form buat user baru |
| `resources/views/users/edit.blade.php` | Form edit user + toggle is_active |

## Seeder
`database/seeders/DatabaseSeeder.php` — membuat 5 role dan 5 default user:

```php
$roles = ['admin', 'manager', 'developer', 'marketing', 'customer'];
foreach ($roles as $role) {
    Role::create(['name' => $role, 'guard_name' => 'web']);
}
```

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat daftar user | ✓ | — | — | — | — |
| Buat / Edit / Hapus user | ✓ | — | — | — | — |
| Assign role | ✓ | — | — | — | — |
