# API Role, Permission, Activity Log, Invoice & Profile — Panduan Mobile

Base URL: `https://flovig.com/api` · Header: `Authorization: Bearer <token>`, `Accept: application/json`
(Login: lihat `docs/API_SUBSCRIPTION_MOBILE.md` §1.)

| Screen | Endpoint | Permission |
|---|---|---|
| RolesScreen | `GET /roles` | role admin/member (dropdown form user juga pakai ini) |
| | `POST /roles`, `GET/PUT/DELETE /roles/{id}` | `manage permissions` |
| PermissionsScreen | `GET /permissions`, `PUT /permissions/{role_name}`, `POST /permissions/{role_name}/reset` | `manage permissions` |
| ActivityLogsScreen | `GET /activity-logs` | `manage permissions` |
| Invoices | `GET /invoices`, `GET /invoices/{id}`, `GET /invoices/{id}/pdf` | `access invoices` |
| | `POST /invoices` | `create invoice` |
| | `PUT /invoices/{id}`, `PUT /invoices/{id}/send`, `PUT /invoices/{id}/mark-paid` | `update invoice` |
| ProfileScreen | `GET/PUT /profile`, `POST/DELETE /profile/avatar`, `PUT /profile/password` | semua user login (profil milik sendiri) |

Error umum: `401` token invalid · `403` tidak punya permission / data company lain · `404` tidak ada · `422` validasi / aturan bisnis (`{ "message": "...", "errors": {...} }`).

---

## 1. Roles

### GET `/roles?search=`
Array (tanpa paginate). Role sistem (`admin`, `member`, `client`) + role custom milik company sendiri.
```json
[
  { "id": 2, "name": "member", "label": "Member", "guard_name": "web",
    "is_system": true, "can_edit": false, "can_delete": false, "users_count": 4,
    "created_at": "2026-09-17T18:57:46+07:00", "updated_at": "..." }
]
```
`users_count` = jumlah user di company Anda. `can_delete` false bila role sistem atau masih dipakai user.

### POST `/roles`
```json
{ "name": "finance" }
```
`name`: wajib, unik, huruf/angka/`-`/`_` (disimpan lowercase). Permission awal = salinan role member.
Response `201`: `{ "message": "Role berhasil dibuat.", "data": { ...role } }`

### GET `/roles/{id}` → objek role.
### PUT `/roles/{id}` `{ "name": "..." }` → `{ message, data }`. Role sistem → `422`.
### DELETE `/roles/{id}` → `{ message }`. Role sistem / masih dipakai user → `422`.

---

## 2. Permissions (matriks)

### GET `/permissions`
```json
{
  "scope": "company",
  "modules": { "task_management": true, "hris": false },
  "groups": [
    { "name": "Menu Akses", "module": "task_management",
      "permissions": [ { "name": "access projects", "label": "Proyek" } ] }
  ],
  "roles": [
    { "id": 2, "name": "member", "label": "Member", "is_system": true,
      "is_customized": false, "permissions": ["access dashboard", "access projects"] }
  ],
  "stats": { "total_permissions": 120, "total_roles": 4 },
  "note": "Role admin selalu memiliki akses penuh dan tidak bisa diubah."
}
```
- `groups` hanya berisi modul yang dibeli company (HRIS disembunyikan bila paket HRIS tidak aktif).
- Role `admin` tidak ada di `roles` (selalu akses penuh).
- `is_customized` true = company sudah mengubah permission role ini (beda dari default).
- Render: baris = `groups[].permissions[]`, kolom = `roles[]`, centang bila `name` ada di `roles[i].permissions`.

### PUT `/permissions/{role_name}`
Kirim **set lengkap** permission yang aktif (bukan diff). Array kosong `[]` = cabut semua.
```json
{ "permissions": ["access dashboard", "access projects", "create task"] }
```
Nama yang tidak dikenal diabaikan. Response:
```json
{ "message": "Permission untuk role Member berhasil disimpan untuk perusahaan Anda.",
  "data": { "role": "member", "is_customized": true, "permissions": ["..."] } }
```

### POST `/permissions/{role_name}/reset`
Kembalikan role ke default sistem. Response sama bentuknya dengan PUT (`is_customized: false`, `permissions` = default).

`{role_name}` memakai **nama** role (mis. `member`), bukan id. `admin` → `422`.

---

## 3. Activity Logs

### GET `/activity-logs?log_name=&search=&causer_id=&date_from=&date_to=&page=&per_page=`
- `log_name`: `project` | `task` | `ticket` | `customer_request` | `payroll_setting` (lihat `log_names` di response)
- `search`: cari di `description` · `causer_id`: filter per user · `date_from`/`date_to`: `YYYY-MM-DD`
- `per_page`: 10, 25, 50, 100 (default 25)

Hanya aktivitas yang dilakukan user di company Anda.
```json
{
  "current_page": 1,
  "data": [
    { "id": 419, "log_name": "project", "log_label": "Proyek",
      "description": "created", "event": "created",
      "subject_type": "Project", "subject_id": 23,
      "causer": { "id": 5, "name": "Budi", "email": "budi@x.com", "avatar_url": null },
      "properties": { "attributes": { "name": "Proyek A" }, "old": {} },
      "created_at": "2026-10-29T14:34:24+07:00" }
  ],
  "last_page": 9, "per_page": 25, "total": 210, "next_page_url": "...",
  "log_names": [ { "value": "project", "label": "Proyek" } ]
}
```
`properties.old` vs `properties.attributes` = nilai sebelum/sesudah (untuk event `updated`).

---

## 4. Invoices

### GET `/invoices?status=&search=&project_id=&page=&per_page=`
- `status`: `draft` | `sent` | `paid` | `overdue`
- `search`: nomor invoice, nama client, atau nama proyek
- `per_page` default 15 (opsi 10, 25, 50, 100)
- User role `client` hanya melihat invoice miliknya.

```json
{
  "current_page": 1,
  "data": [
    { "id": 12, "invoice_number": "INV-202611-0003", "status": "sent",
      "issue_date": "2026-11-01", "due_date": "2026-11-15",
      "subtotal": "1000000.00", "tax": "11.00", "total": "1110000.00", "paid_at": null,
      "project": { "id": 3, "name": "Website" }, "client": { "id": 9, "name": "PT ABC" } }
  ],
  "last_page": 1, "total": 1,
  "stats": {
    "total_amount": 1110000, "paid_amount": 0, "pending_amount": 1110000, "overdue_amount": 0,
    "total_count": 1, "paid_count": 0, "pending_count": 1, "overdue_count": 0
  },
  "can": { "create": true, "update": true }
}
```
`stats` dihitung dari semua invoice (tidak terpengaruh filter status/search) — untuk kartu KPI di atas list.
`can` dipakai untuk menampilkan/menyembunyikan tombol Buat, Kirim, Tandai Lunas.

### GET `/invoices/{id}` → invoice + `project`, `client`, `items[]` (`description`, `quantity`, `unit_price`, `total`).
### GET `/invoices/{id}/pdf` → file PDF (`application/pdf`, download).

### POST `/invoices`
```json
{ "project_id": 3, "client_id": 9, "issue_date": "2026-11-23", "due_date": "2026-12-07",
  "tax": 11, "notes": "opsional",
  "items": [ { "description": "Desain UI", "quantity": 1, "unit_price": 1000000 } ] }
```
Response `201` = invoice lengkap (status `draft`).

### PUT `/invoices/{id}` — ubah `due_date`, `notes`, `tax`, `items` (kirim `items` = ganti semua item). Invoice `paid` → `422`.
### PUT `/invoices/{id}/send` — status → `sent`, client mendapat notifikasi.
### PUT `/invoices/{id}/mark-paid` — status → `paid`, `paid_at` diisi.

---

## 5. Profile (user yang sedang login)

Semua endpoint hanya mengubah profil milik pemilik token. Respons sukses selalu membawa `data` = objek profil terbaru (kecuali ganti password).

### GET `/profile`
```json
{ "data": {
  "id": 5, "name": "Budi Santoso", "email": "budi@contoh.com",
  "avatar": "avatars/abc.jpg", "avatar_url": "https://flovig.com/storage/avatars/abc.jpg",
  "initials": "BS", "timezone": "Asia/Jakarta", "email_notifications_enabled": true,
  "email_verified_at": "2026-10-01T22:34:40+07:00", "company_id": 2,
  "roles": ["admin"], "has_google": false, "created_at": "2026-09-18T01:59:22+07:00" } }
```
`avatar_url` null → tampilkan lingkaran `initials`. Email tidak bisa diubah lewat API.

### PUT `/profile`
Kirim hanya field yang diubah (semua opsional):
```json
{ "name": "Budi Santoso", "timezone": "Asia/Makassar", "email_notifications_enabled": false }
```
- `name`: string, maks 255 (jika dikirim tidak boleh kosong).
- `timezone`: nama timezone valid / `null`. Pilihan di web: `Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura`, `UTC`.
- `email_notifications_enabled`: boolean.

Response: `{ "message": "Informasi profil berhasil diperbarui.", "data": { ...profil } }`

### POST `/profile/avatar`
`multipart/form-data`, field `avatar` (jpg/jpeg/png/gif/webp, maks 2 MB). **Pakai POST, bukan PUT.** Foto lama dihapus otomatis.
Response: `{ "message": "Foto profil berhasil diperbarui.", "data": { ...profil } }`

### DELETE `/profile/avatar`
Response: `{ "message": "Foto profil berhasil dihapus.", "data": { ...profil, "avatar": null, "avatar_url": null } }`

### PUT `/profile/password`
```json
{ "current_password": "lama", "password": "BaruMin8", "password_confirmation": "BaruMin8" }
```
Password lama salah → `422` `errors.current_password`. Sukses → `{ "message": "Password berhasil diubah." }`.
Token di perangkat lain otomatis dicabut; token yang sedang dipakai tetap berlaku (tidak perlu login ulang).
