# API Langganan (Subscription) — Panduan Mobile

Base URL: `https://flovig.com/api`
Pembayaran: **DOKU Checkout** (app membuka `payment_url` di WebView / browser).
Koleksi Postman: `docs/postman/Flovig-Billing-API.postman_collection.json`

---

## 1. Autentikasi

Semua endpoint billing butuh token Sanctum.

```http
POST /api/auth/login
Content-Type: application/json

{ "email": "user@company.com", "password": "secret" }
```

Response: `{ "user": {...}, "token": "1|abc..." }`

Header untuk semua request berikutnya:

```http
Authorization: Bearer <token>
Accept: application/json
```

> Endpoint billing **tidak** diblok middleware masa aktif, jadi user yang langganannya sudah habis tetap bisa memperpanjang dari app.

---

### Lupa Password (publik, tanpa token)
Maks 6 request/menit per IP.

**POST `/auth/forgot-password`** `{ "email": "user@mail.com" }`
→ `200` `{ "message": "Jika email terdaftar, token reset password sudah dikirim...", "resend_after": 60 }`
Respons selalu sama walau email tidak terdaftar. Email berisi token 64 karakter, berlaku 60 menit, sekali pakai. Kirim ulang ke user yang sama baru diproses setelah 60 detik.

**POST `/auth/reset-password`**
```json
{ "email": "user@mail.com", "token": "<dari email>", "password": "BaruSekali123!", "password_confirmation": "BaruSekali123!" }
```
→ `200` `{ "message": "Password berhasil direset. Silakan login dengan password baru." }`
→ `422` `errors.token` (token salah/kedaluwarsa/beda email) atau `errors.password` (validasi).
Setelah berhasil, semua token login (Sanctum) user tersebut dicabut, jadi user harus login ulang.

## 2. Ringkasan Endpoint

| Method | Endpoint | Fungsi | Permission |
|---|---|---|---|
| GET | `/billing/subscription` | Status langganan perusahaan saat ini | login |
| GET | `/billing/usage` | Pemakaian kuota project, user, storage (+ rincian storage) | `access billing` |
| GET | `/billing/packages` | Daftar paket yang bisa dibeli | login |
| GET | `/billing/payment-methods` | Metode pembayaran aktif + biaya layanan | login |
| GET | `/billing/packages/{package_id}/quote?users=&cycle=` | Rincian harga (PPN, promo, fee per metode) | login |
| POST | `/billing/checkout/{package_id}` | Buat order + link bayar DOKU | login |
| GET | `/billing/orders` | Riwayat transaksi (paginate) | `access billing` |
| GET | `/billing/orders/{order_number}` | Detail order (dipakai polling status) | `access billing` |
| GET | `/billing/orders/{order_number}/pay` | Ambil ulang link bayar order pending | `access billing` |
| GET | `/billing/orders/{order_number}/invoice` | Download invoice PDF | `access billing` |

`{package_id}` = **id numerik** paket. `{order_number}` = string seperti `SUB-20261122101530-AB12CD`.

---

## 3. Alur Subscribe di Mobile

```
[Login] → GET subscription (tampilkan status / sisa hari)
        → GET packages  (pilih paket, jumlah user, siklus monthly/yearly)
        → GET packages/{id}/quote?users=&cycle=   (tampilkan rincian + fee tiap metode)
        → POST checkout/{id}  { users, cycle, payment_method }
        → buka payment_url di WebView
        → saat WebView redirect ke  https://flovig.com/billing/finish/{order_number}
          tutup WebView
        → polling GET orders/{order_number} sampai status != "pending"
        → status "paid" → refresh GET subscription
```

Catatan:
- Status final ditentukan oleh **notifikasi server-to-server DOKU**, bukan oleh redirect. Redirect ke `/billing/finish/...` hanya tanda user selesai di halaman DOKU — tetap polling `orders/{order_number}`.
- Polling disarankan tiap 3–5 detik, maksimal ±2 menit; setelah itu tampilkan "Menunggu konfirmasi pembayaran" dan biarkan user cek di Riwayat.
- Link bayar berlaku sampai `payment_expires_at` (default 60 menit). Kalau user keluar sebelum bayar, pakai `GET orders/{order_number}/pay` untuk melanjutkan.

---

## 4. Detail Endpoint

### 4.1 GET `/billing/subscription`

```json
{
  "package": { "id": 2, "slug": "pro", "name": "Pro" },
  "plan_name": "Pro",
  "active_until": "2026-12-31T23:59:59+07:00",
  "is_lifetime": false,
  "is_expired": false,
  "days_remaining": 39,
  "limits": {
    "max_users": 20,
    "user_count": 8,
    "max_projects": 50,
    "project_count": 12,
    "max_storage_bytes": 10737418240,
    "storage_used_bytes": 1234567
  }
}
```

`package` bisa `null` (belum punya paket). Nilai `max_*` bisa `null` = tanpa batas.

### 4.1b GET `/billing/usage` — menu Usage

```json
{
  "plan_name": "Free",
  "project": { "used": 2, "limit": 3, "remaining": 1, "percent": 66.7, "can_add": true },
  "user": {
    "used": 2, "limit": 8, "remaining": 6, "percent": 25, "can_add": true,
    "note": "User aktif & nonaktif dihitung; akun client tidak dihitung."
  },
  "storage": {
    "used_bytes": 52428800,
    "used_label": "50 MB",
    "limit_bytes": 1073741824,
    "limit_label": "1 GB",
    "remaining_bytes": 1021313024,
    "percent": 4.9,
    "breakdown": [
      { "menu": "Project Files", "bytes": 41943040, "label": "40 MB" },
      { "menu": "Lampiran Task", "bytes": 10485760, "label": "10 MB" }
    ]
  }
}
```

- `limit`, `remaining`, `percent` = `null` → **Unlimited** (tampilkan tanpa progress bar).
- Warna bar yang dipakai web: < 80% biru, 80–99% kuning, 100% merah.
- `breakdown` hanya berisi menu yang ada file-nya, urut dari yang terbesar.
- Kuota user menghitung user aktif **dan** nonaktif; slot baru kosong kalau user dihapus.
- Saat kuota habis, endpoint tambah project/user/upload membalas `403` dengan `message` + `upgrade_url`.

### 4.2 GET `/billing/packages`

```json
{
  "data": [
    {
      "id": 2,
      "slug": "pro",
      "name": "Pro",
      "tagline": "...",
      "description": "...",
      "price_per_user": 50000,
      "price_per_user_yearly": 500000,
      "yearly_saving_percent": 17,
      "min_users": 1,
      "max_users": 50,
      "max_projects": null,
      "storage_label": "10 GB",
      "is_popular": true,
      "cta_type": "register",
      "features": ["Unlimited project", "..."],
      "is_owned": true
    }
  ],
  "contact_package": { "...": "paket Enterprise (hubungi sales), tidak bisa checkout" },
  "ppn_rate": 11,
  "default_user_count": 8,
  "cycles": ["monthly", "yearly"]
}
```

- `is_owned: true` → paket yang sedang dipakai (label "Paket Anda" / tombol "Perpanjang").
- `default_user_count` → isi awal input jumlah user (jumlah user aktif perusahaan).
- `contact_package` → tampilkan sebagai kartu "Hubungi kami", jangan panggil checkout.

### 4.3 GET `/billing/payment-methods`

```json
{
  "data": [
    { "code": "VIRTUAL_ACCOUNT_BCA", "name": "BCA Virtual Account", "group": "Virtual Account", "fee_flat": 4000, "fee_percent": 0 },
    { "code": "QRIS", "name": "QRIS", "group": "E-Wallet", "fee_flat": 0, "fee_percent": 0.7 }
  ]
}
```

Kelompokkan tampilan berdasarkan `group`. Kirim `code` saat checkout.

### 4.4 GET `/billing/packages/{package_id}/quote`

Query: `users` (int, opsional, default = user aktif), `cycle` (`monthly`|`yearly`, default `monthly`).

```json
{
  "package": { "...": "sama seperti 4.2" },
  "quote": {
    "users": 8,
    "cycle": "monthly",
    "months": 1,
    "price_per_user": 50000,
    "gross": 400000,
    "promo": { "id": 3, "name": "Promo Akhir Tahun" },
    "discount": 40000,
    "subtotal": 360000,
    "ppn_rate": 11,
    "ppn": 39600,
    "total": 399600,
    "duration_days": 30
  },
  "fees": [
    { "code": "VIRTUAL_ACCOUNT_BCA", "name": "BCA Virtual Account", "fee": 4000, "total": 403600 }
  ]
}
```

- `promo` bisa `null`.
- Total yang dibayar user = `fees[i].total` sesuai metode yang dipilih (biaya layanan ditanggung customer).
- Panggil ulang setiap kali user mengubah jumlah user / siklus.

### 4.5 POST `/billing/checkout/{package_id}`

```http
POST /api/billing/checkout/2
Content-Type: application/json

{ "users": 8, "cycle": "monthly", "payment_method": "VIRTUAL_ACCOUNT_BCA" }
```

Response `201`:

```json
{
  "message": "Order dibuat. Lanjutkan pembayaran melalui payment_url.",
  "payment_url": "https://checkout.doku.com/...",
  "data": { "...": "objek Order, lihat bagian 5" }
}
```

Error:

| Kode | Arti |
|---|---|
| 422 | Validasi gagal / `payment_method` tidak tersedia (`errors.payment_method`) |
| 404 | Paket tidak aktif / gratis, atau data pendaftar perusahaan tidak ditemukan |
| 502 | Gagal membuat transaksi di DOKU — tampilkan "coba lagi" |

### 4.6 GET `/billing/orders`

Query: `page`, `per_page`. Response format paginate Laravel:

```json
{
  "current_page": 1,
  "data": [ { "...": "objek Order" } ],
  "last_page": 3,
  "per_page": 15,
  "total": 34
}
```

### 4.7 GET `/billing/orders/{order_number}`

`{ "data": { ...objek Order } }` — dipakai untuk polling status setelah bayar.

### 4.8 GET `/billing/orders/{order_number}/pay`

```json
{ "payment_url": "https://checkout.doku.com/...", "payment_expires_at": "2026-11-22T11:15:30+07:00" }
```

`422` bila order sudah tidak bisa dibayar (lunas/gagal/kedaluwarsa) → arahkan user membuat checkout baru.

### 4.9 GET `/billing/orders/{order_number}/invoice`

Response file PDF (`application/pdf`, `invoice-{order_number}.pdf`). Download dengan header Authorization, lalu buka / share file.

---

## 5. Objek Order

```json
{
  "id": 101,
  "order_number": "SUB-20261122101530-AB12CD",
  "package": { "id": 2, "slug": "pro", "name": "Pro" },
  "billing_cycle": "monthly",
  "user_count": 8,
  "cycle_label": "Bulanan",
  "amount": 403600,
  "amount_label": "Rp 403.600",
  "breakdown": {
    "gross": 400000,
    "promo_name": "Promo Akhir Tahun",
    "discount": 40000,
    "subtotal": 360000,
    "ppn_rate": 11,
    "ppn": 39600,
    "fee": 4000,
    "total": 403600
  },
  "payment_method": { "code": "VIRTUAL_ACCOUNT_BCA", "name": "BCA Virtual Account" },
  "status": "pending",
  "status_label": "Menunggu Pembayaran",
  "is_payable": true,
  "duration_days": 30,
  "created_at": "2026-11-22T10:15:30+07:00",
  "paid_at": null,
  "payment_expires_at": "2026-11-22T11:15:30+07:00",
  "invoice_url": "https://flovig.com/api/billing/orders/SUB-20261122101530-AB12CD/invoice"
}
```

### Status

| `status` | `status_label` | Aksi di app |
|---|---|---|
| `pending` | Menunggu Pembayaran | Tombol "Bayar" bila `is_payable` = true |
| `paid` | Lunas | Tombol "Invoice" |
| `failed` | Gagal | Tawarkan checkout ulang |
| `expired` | Kedaluwarsa | Tawarkan checkout ulang |

Order `pending` yang sudah lewat `payment_expires_at` otomatis dikirim sebagai `expired`.

Semua nominal dalam **Rupiah, integer** (tanpa desimal). Semua tanggal ISO-8601.

---

## 6. Error Umum

| Kode | Arti |
|---|---|
| 401 | Token tidak valid / kedaluwarsa → login ulang |
| 403 | Tidak punya permission `access billing` (riwayat/invoice) atau order milik perusahaan lain |
| 404 | Paket / order tidak ditemukan |
| 422 | Validasi; body `{ "message": "...", "errors": { "field": ["..."] } }` |
