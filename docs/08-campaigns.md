# Modul 08 — Campaigns & Leads

## Deskripsi
Manajemen kampanye pemasaran dan pelacakan lead. Kampanye bisa terhubung ke proyek dan memiliki banyak lead. Tersedia konversi lead status dan perhitungan conversion rate otomatis.

## Routes

| Method | URI | Name | Keterangan |
|--------|-----|------|------------|
| GET | `/campaigns` | `campaigns.index` | Daftar kampanye (card grid) |
| GET | `/campaigns/create` | `campaigns.create` | Form buat kampanye |
| POST | `/campaigns` | `campaigns.store` | Simpan kampanye baru |
| GET | `/campaigns/{campaign}` | `campaigns.show` | Detail + daftar lead |
| GET | `/campaigns/{campaign}/edit` | `campaigns.edit` | Form edit kampanye |
| PUT | `/campaigns/{campaign}` | `campaigns.update` | Update kampanye |
| DELETE | `/campaigns/{campaign}` | `campaigns.destroy` | Hapus kampanye |
| POST | `/campaigns/{campaign}/leads` | `campaigns.leads.store` | Tambah lead ke kampanye |
| PUT | `/leads/{lead}` | `leads.update` | Update status lead |

## Model: Campaign
`app/Models/Campaign.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `project_id` | FK → projects | Proyek terkait (nullable) |
| `name` | string | Nama kampanye |
| `description` | text | Deskripsi |
| `channel` | enum | `email`, `social_media`, `ads`, `event`, `referral`, `other` |
| `status` | enum | `draft`, `active`, `paused`, `completed`, `cancelled` |
| `start_date` | date | Tanggal mulai |
| `end_date` | date | Tanggal berakhir |
| `budget` | decimal | Anggaran kampanye |
| `target_leads` | integer | Target jumlah lead |

### Accessor
- `conversion_rate` — persentase lead dengan status `converted` dari total lead

### Relasi
- `project` → `Project` (belongsTo)
- `leads` → `Lead` (hasMany)

## Model: Lead
`app/Models/Lead.php`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `campaign_id` | FK → campaigns | Kampanye terkait |
| `name` | string | Nama lead/prospek |
| `email` | string | Email |
| `phone` | string | Nomor telepon (nullable) |
| `company` | string | Perusahaan (nullable) |
| `status` | enum | `new`, `contacted`, `qualified`, `proposal`, `negotiation`, `converted`, `lost` |
| `notes` | text | Catatan (nullable) |
| `source` | string | Sumber lead |

## Controller
`app/Http/Controllers/Web/CampaignWebController.php`

| Method | Fungsi |
|--------|--------|
| `index()` | Daftar kampanye dengan conversion_rate |
| `create()` | Form buat kampanye + pilih proyek |
| `store()` | Simpan kampanye |
| `show()` | Detail kampanye + tabel lead + modal tambah lead |
| `edit()` | Form edit kampanye |
| `update()` | Update kampanye |
| `destroy()` | Hapus kampanye |
| `storeLead()` | Tambah lead ke kampanye |
| `updateLead()` | Update status lead |

## Views

| View | Deskripsi |
|------|-----------|
| `resources/views/campaigns/index.blade.php` | Card grid kampanye + emoji icon channel |
| `resources/views/campaigns/create.blade.php` | Form buat kampanye |
| `resources/views/campaigns/edit.blade.php` | Form edit kampanye |
| `resources/views/campaigns/show.blade.php` | Detail + tabel lead + modal add lead (Alpine.js) |

### Icon Channel (di index.blade.php)
| Channel | Emoji |
|---------|-------|
| email | 📧 |
| social_media | 📱 |
| ads | 📣 |
| event | 🎪 |
| referral | 🤝 |
| other | 📌 |

## Pipeline Lead
```
new → contacted → qualified → proposal → negotiation
                                              ↙        ↘
                                        converted      lost
```

## Akses Berdasarkan Role

| Aksi | Admin | Manager | Developer | Marketing | Customer |
|------|-------|---------|-----------|-----------|----------|
| Lihat | ✓ | ✓ | — | ✓ | — |
| CRUD kampanye | ✓ | ✓ | — | ✓ | — |
| Kelola lead | ✓ | ✓ | — | ✓ | — |
