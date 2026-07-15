# Panduan Sistem Flovig (ProjectHub)

Dokumen ini menjelaskan alur kerja dan cara pakai Flovig — dipakai juga sebagai
sumber pengetahuan untuk AI Assistant di dalam aplikasi (lihat
`app/Http/Controllers/Web/AiAssistantWebController.php`), supaya jawaban AI
sesuai dengan sistem yang sesungguhnya, bukan jawaban generik.

## 1. Apa itu Flovig

Flovig adalah aplikasi web terpadu untuk **manajemen proyek/tim (Task
Management)** dan **HRIS (Human Resource Information System)**. Satu
perusahaan bisa berlangganan salah satu atau kedua modul. Kalau berlangganan
keduanya, user bisa **pindah modul** lewat tombol switcher (biasanya di
header/sidebar) — sistem menyimpan modul aktif di session (`active_package`:
`task_management` atau `hris`), jadi menu sidebar berubah sesuai modul yang
sedang dipilih.

## 2. Role & Permission

Role tersedia: **admin, manager, developer, marketing, customer**.

- **admin** — otomatis punya semua permission (bypass lewat `Gate::before`),
  akses penuh ke semua fitur termasuk Master Data dan Manajemen Permission.
- **manager** — akses luas ke Proyek, Tiket, Approval, HRIS (kelola absensi,
  pendaftaran wajah), kelola user.
- **developer / marketing** — akses ke fitur operasional harian sesuai
  fungsinya (developer: tiket & sprint; marketing: campaign).
- **customer** — akses terbatas: cuma lihat proyek miliknya sendiri (lewat
  relasi `client_id`), bisa chat di proyek itu, buat ticket/request, lihat
  invoice miliknya.

**Penting:** hak akses **tidak hardcode per role** — semua diatur lewat sistem
permission dinamis (Spatie Permission). Admin bisa mengatur ulang siapa boleh
apa lewat menu **Master Data → Permission Management** (`/permissions`),
tanpa perlu ubah kode. Role default di atas cuma nilai awal (lihat
`database/seeders/PermissionSeeder.php`).

## 3. Modul Task Management

Menu ini muncul saat modul aktif = `task_management`.

| Menu | Fungsi |
|---|---|
| **Proyek** | Kelola proyek: buat proyek, tambah anggota tim, kelola task/milestone/sprint, budget, risiko, file, timesheet. Customer cuma lihat proyek yang `client_id`-nya dia. |
| **Bug Tickets** | Laporan bug/masalah teknis dari customer atau internal, bisa di-assign ke developer, ada status open/resolved. |
| **Approvals** | Pusat persetujuan lintas modul (request, cuti, lembur, reimburse, dll) — approver ditentukan berdasarkan **Approval Policy** yang dikonfigurasi admin. |
| **Chat** | Satu halaman, 3 tab: **Proyek** (chat per proyek, anggotanya = anggota proyek), **Pesan** (chat 1-ke-1 antar siapa saja di company yang sama), **Forum** (grup chat, dibuat siapa saja lalu undang anggota). |
| **Customer Requests** | Permintaan dari customer (di luar bug ticket), perlu approve/reject. |
| **Campaigns** | Kelola campaign marketing & leads. |
| **Invoice** | Buat, kirim, dan tandai lunas invoice ke customer. |
| **Kalender** | Jadwal/deadline lintas proyek. |
| **Templates** | Template proyek siap pakai (buat proyek baru dari template). |
| **Workload** | Lihat beban kerja tiap anggota tim (jumlah task aktif). |
| **Analytics** | Statistik & laporan proyek/tim. |
| **Anggota Tim** (Users) | Kelola daftar user internal. |
| **Clients** | Kelola daftar customer (admin & manager). |

### Alur bikin proyek baru
1. Proyek → **Buat Proyek** → isi nama, deskripsi, pilih client & manager,
   tanggal mulai/selesai, budget.
2. Buka proyek → tambah anggota tim lewat tab anggota.
3. Buat milestone/sprint, lalu task di dalamnya, assign ke anggota.
4. Progress otomatis terhitung dari status task.
5. Chat proyek otomatis tersedia untuk manager & anggota (tab **Proyek** di
   menu Chat).

## 4. Modul HRIS

Menu ini muncul saat modul aktif = `hris`.

### Karyawan
- **Data Karyawan** — daftar karyawan perusahaan.
- **Absensi** — halaman check-in/check-out harian:
  - Kalau **Validasi Lokasi (GPS)** aktif, karyawan harus dalam radius
    tertentu dari titik koordinat kantor.
  - Kalau **Pengenalan Wajah** aktif, karyawan verifikasi wajah lewat kamera
    (diproses di browser pakai face-api.js, deskriptor wajah tersimpan di
    server, tidak ada gambar wajah yang disimpan/dikirim ke pihak luar).
    Karyawan bisa **daftarkan wajah sendiri** langsung dari halaman Absensi
    kalau belum terdaftar.
  - Rekap kehadiran per bulan bisa dilihat sendiri; admin/manager (dengan
    permission `manage absensi`) bisa lihat rekap semua karyawan.

### Pengajuan
- **Cuti & Izin** — karyawan ajukan, disetujui oleh yang punya permission
  `approve leave`/`manage leave`.
- **Lembur** — sama polanya, permission `approve overtime`/`manage overtime`.
- **Reimburse** — klaim penggantian biaya, permission
  `approve reimbursement`/`manage reimbursement`.

### Administrasi (khusus yang punya permission)
- **Penggajian** — proses payroll bulanan (`manage payroll`,
  `generate payroll`).
- **Konfigurasi Absensi** — atur validasi lokasi (koordinat kantor, radius
  maksimum) dan pengenalan wajah (sensitivitas, wajib saat check-out atau
  tidak) — permission `manage absensi` (default: **admin saja**).
- **Pendaftaran Wajah** — halaman khusus untuk daftarkan/perbarui/hapus data
  wajah karyawan mana pun — permission `manage face enrollment` (default:
  **admin & manager**), terpisah dari Konfigurasi Absensi supaya manager bisa
  bantu daftarkan wajah tanpa perlu akses pengaturan penuh.
- **Konfigurasi HRIS** — master data HRIS: jenis cuti, aturan lembur, PTKP,
  tarif PPh21 (permission `manage hris master`).

## 5. Master Data (Admin)

Struktur Organisasi, Perusahaan, Branch, Divisi, Departemen, Level
Struktural, Approval Policies (siapa approve apa), Manajemen Role &
Permission, Activity Log (jejak audit aksi penting di sistem).

## 6. AI Assistant

Tombol bulat ungu di pojok kanan bawah setiap halaman. AI ini **self-hosted**
(model Llama 3.2 3B lewat Ollama, jalan di server sendiri) — pertanyaan tidak
dikirim ke pihak ketiga. Riwayat chat tersimpan di browser (localStorage)
masing-masing user, bisa dihapus kapan saja lewat ikon tempat sampah di
header widget. AI ini dibekali ringkasan sistem ini (dokumen ini) sebagai
konteks, supaya bisa menjawab pertanyaan seputar cara pakai Flovig secara
akurat — tapi tetap tidak tahu data pribadi/spesifik akun user (proyek,
absensi, dll) kecuali diberi tahu langsung dalam percakapan.

## 7. Istilah Penting

- **Package** — modul berlangganan perusahaan: `task_management` dan/atau
  `hris`.
- **Permission** — hak akses granular per aksi (bukan per role), diatur di
  `/permissions`.
- **Approval Policy** — aturan siapa yang harus approve suatu jenis
  pengajuan (cuti, lembur, request, dll), dikonfigurasi terpisah dari
  permission biasa.
- **client_id** — kolom di tabel proyek yang menghubungkan proyek ke akun
  customer pemiliknya; dipakai untuk membatasi apa yang customer bisa lihat.
