# Modul 09 — Invoices

## Deskripsi
Pembuatan dan pengelolaan invoice dengan line items dinamis, kalkulasi real-time (Alpine.js), pengiriman ke client, dan ekspor PDF menggunakan barryvdh/laravel-dompdf.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/invoices` | `invoices.index` | Daftar invoice |
| GET | `/invoices/create` | `invoices.create` | Form buat invoice + dynamic items |
| POST | `/invoices` | `invoices.store` | Simpan invoice |
| GET | `/invoices/{invoice}` | `invoices.show` | Detail invoice |
| PUT | `/invoices/{invoice}/send` | `invoices.send` | Kirim invoice ke client |
| PUT | `/invoices/{invoice}/mark-paid` | `invoices.markPaid` | Tandai sebagai lunas |
| GET | `/invoices/{invoice}/pdf` | `invoices.pdf` | Download PDF |

## Model: Invoice
`app/Models/Invoice.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `project_id` | FK → projects | Proyek terkait (nullable) |
| `client_id` | FK → users | Client penerima |
| `invoice_number` | string | Nomor unik (format: INV-YYYYMM-0001) |
| `status` | enum | `draft`, `sent`, `paid`, `overdue`, `cancelled` |
| `issue_date` | date | Tanggal terbit |
| `due_date` | date | Jatuh tempo |
| `subtotal` | decimal | Total sebelum pajak |
| `tax_rate` | decimal | Persentase pajak (default 11%) |
| `tax_amount` | decimal | Nominal pajak |
| `total` | decimal | Total akhir |
| `notes` | text | Catatan (opsional) |
| `paid_at` | datetime | Waktu pelunasan |
| `sent_at` | datetime | Waktu invoice dikirim |

### Method
- `recalculate()` — hitung ulang subtotal, tax_amount, total dari items
- `generateNumber()` (static) — generate nomor invoice unik: `INV-{YYYYMM}-{4digit}`

### Relasi
- `project` → `Project` (belongsTo)
- `client` → `User` (belongsTo)
- `items` → `InvoiceItem` (hasMany)

## Model: InvoiceItem
`app/Models/InvoiceItem.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `invoice_id` | FK → invoices | Invoice induk |
| `description` | string | Keterangan item |
| `quantity` | decimal | Jumlah |
| `unit_price` | decimal | Harga satuan |
| `amount` | decimal | Total (qty × unit_price) |

## Controller
`app/Http/Controllers/Web/InvoiceWebController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Daftar invoice (filter by role: customer hanya miliknya) |
| `create()` | Form + daftar client + proyek |
| `store()` | Simpan invoice + items + `recalculate()` |
| `show()` | Detail invoice + daftar items |
| `send()` | Set status `sent`, set `sent_at`, notifikasi client |
| `markPaid()` | Set status `paid`, set `paid_at` |
| `downloadPdf()` | Render `invoices/pdf.blade.php` via DomPDF |

## Dynamic Line Items (Alpine.js)

Form create menggunakan Alpine.js `invoiceForm()` function:
- Tambah / hapus baris item secara real-time
- Auto-kalkulasi `amount = qty × unit_price` per baris
- Auto-kalkulasi `subtotal`, `tax_amount`, `total` secara live
- Tax rate default: 11%

```javascript
// Struktur Alpine data
{
    items: [{description, quantity, unit_price, amount}],
    tax_rate: 11,
    subtotal: 0,
    tax_amount: 0,
    total: 0,
    addItem(), removeItem(index), recalculate()
}
```

## PDF Export
- Template: `resources/views/invoices/pdf.blade.php`
- Library: `barryvdh/laravel-dompdf`
- Output: `Invoice-{invoice_number}.pdf` (attachment download)

## Scheduler
`artisan invoices:check-overdue` — berjalan **setiap hari** (via Laravel Scheduler):
- Cari invoice dengan status `sent` dan `due_date < today`
- Update status ke `overdue`
- Kirim notifikasi ke manager

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/invoices/index.blade.php` | Tabel invoice + badge status |
| `resources/views/invoices/create.blade.php` | Form + Alpine.js dynamic line items |
| `resources/views/invoices/show.blade.php` | Detail + tombol send/mark-paid/download PDF |
| `resources/views/invoices/pdf.blade.php` | Template cetak PDF (DomPDF) |

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat semua | ✓ | ✓ | — | — | hanya miliknya |
| Buat invoice | ✓ | ✓ | — | — | — |
| Kirim / Mark Paid | ✓ | ✓ | — | — | — |
| Download PDF | ✓ | ✓ | — | — | ✓ |
