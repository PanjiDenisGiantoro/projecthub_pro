# API AI Assistant (Runa) — Panduan Mobile

Base URL: `https://flovig.com/api` · Header: `Authorization: Bearer <token>`, `Accept: application/json`, `Content-Type: application/json`
(Login: lihat `docs/API_SUBSCRIPTION_MOBILE.md` §1.)

Runa = model AI self-hosted (Ollama, `llama3.2:1b`, jalan di CPU server). Backend sama dengan widget web
(`AiAssistantWebController`), jadi perilaku & permission identik.

| Endpoint | Fungsi | Limit |
|---|---|---|
| `POST /ai/chat` | Kirim percakapan, dapat balasan (streaming teks **atau** JSON usulan aksi) | 20 req/menit/user |
| `POST /ai/execute-action` | Eksekusi aksi yang sudah dikonfirmasi user (buat proyek / task, ubah task) | 20 req/menit/user |

Server **tidak menyimpan riwayat chat** — app yang menyimpan & mengirim ulang riwayat tiap request.

---

## 1. POST `/ai/chat`

### Request
```json
{
  "messages": [
    { "role": "user", "content": "Cara mengajukan cuti gimana?" },
    { "role": "assistant", "content": "Buka menu Cuti & Izin ..." },
    { "role": "user", "content": "Kalau lembur?" }
  ]
}
```
- `messages`: wajib, 1–20 item (kirim 20 pesan terakhir saja; system prompt ditambahkan server).
- `messages.*.role`: `user` | `assistant`
- `messages.*.content`: string, maks 4000 karakter.

### Response — bedakan dari header `Content-Type`

**A. `text/plain; charset=utf-8` (mayoritas)** — status `200`, body adalah teks balasan yang dikirim
**bertahap (chunked)** begitu model generate. Baca sebagai stream dan append ke bubble chat; stream selesai
= balasan selesai. Bukan SSE dan bukan JSON — langsung potongan teks UTF-8 (decode dengan decoder
yang tahan karakter multi-byte terpotong antar chunk).

Pertanyaan data yang jelas dijawab **langsung dari database** (bukan model), juga sebagai `text/plain` —
datang sekaligus dalam <1 detik. Semua dibatasi hak akses user, sama dengan halaman web:

| Contoh pertanyaan | Data | Batasan akses |
|---|---|---|
| "tugas saya", "deadline minggu ini/hari ini/besok/bulan ini", "task yang terlambat" | Task terbuka yang di-assign ke user | task milik user sendiri |
| "progress proyek Company Website Revamp" | Status, progress, task, tiket, milestone berikutnya | proyek yang bisa diakses user |
| "tiket saya", "ada tiket bug apa saja" | Tiket bug terbuka | proyek yang bisa diakses user |
| "absensi saya hari ini" | Check-in/out hari ini + rekap bulan | paket HRIS aktif, bukan client/customer |
| "sisa cuti saya" | Saldo cuti tahun berjalan | idem |
| "status cuti / lembur / reimburse saya" | 5 pengajuan terakhir milik user | idem |
| "apa yang perlu saya approve" | Pengajuan pending yang boleh di-approve user | permission `approve leave/overtime/reimbursement` + approval per role |
| "siapa saja anggota tim" | Anggota aktif | permission `access users` |
| "proyek apa saja" | Daftar proyek | proyek yang bisa diakses user |

Kalimat "cara/bagaimana/mau/ajukan/buat …" tetap dijawab model (minta panduan, bukan data).
Untuk pertanyaan lain, server otomatis menyertakan ringkasan tim, proyek & jumlah task user ke konteks model —
**app tidak perlu menyisipkan data apa pun ke `messages`**.

Kalau AI sibuk/mati di tengah jalan, status tetap `200` dan teks yang dikirim:
`AI Assistant sedang sibuk/lambat merespons. Coba lagi sesaat lagi.`

**B. `application/json`** — dipakai saat pesan user terlihat minta membuat proyek/task atau mengubah task:

Usulan aksi (tampilkan kartu konfirmasi berisi `label` + tombol "Jalankan"):
```json
{
  "reply": "",
  "action": {
    "tool": "create_project",
    "label": "Buat proyek baru",
    "args": { "name": "Aplikasi Kasir", "description": "" }
  }
}
```
```json
{
  "reply": "",
  "action": {
    "tool": "create_task",
    "label": "Buat task \"Desain login\" di proyek \"Aplikasi Kasir\"",
    "args": { "project_name": "Aplikasi Kasir", "title": "Desain login", "description": "" }
  }
}
```
```json
{
  "reply": "",
  "action": {
    "tool": "update_task",
    "label": "Ubah deadline task \"Desain login\" (proyek Aplikasi Kasir)",
    "args": { "task_id": 340, "field": "due_date", "value": "2026-11-27" },
    "details": [
      { "label": "Deadline lama", "value": "12 Nov 2026" },
      { "label": "Deadline baru", "value": "Jumat, 27 Nov 2026" }
    ]
  }
}
```
**`details`** (opsional, saat ini hanya ada di `update_task`) berisi daftar `label: value` yang
ditampilkan di kartu konfirmasi (perubahan lama → baru). Kalau tidak ada, tampilkan `args.name`/`args.title`.

#### Perintah ubah task (`update_task`)
Dikenali langsung oleh server (tidak lewat model), hanya untuk task di proyek yang bisa diakses user:

| Contoh kalimat | `field` | `value` |
|---|---|---|
| "tandai task Desain login selesai", "task Desain login sudah selesai" | `done` | `true` |
| "pindahkan task Desain login ke Review" | `status` | id kolom board |
| "ubah deadline task Desain login jadi 30 Nov / besok / jumat" | `due_date` | `YYYY-MM-DD` |
| "assign task Desain login ke Siti" | `assignee` | user id |
| "ubah prioritas task Desain login jadi tinggi" | `priority` | `low/medium/high/urgent` |
| "komentar di task Desain login: tolong cek lagi" | `comment` | isi komentar |

Kalau judul task ada di beberapa proyek, user menambahkan "di proyek <nama>" setelah judul. Kalau task,
kolom, tanggal, atau orangnya tidak ketemu atau ambigu, server membalas teks `{ "reply": "..." }` berisi
pertanyaan klarifikasi, tanpa `action`.

Atau balasan teks biasa (tanpa `action`):
```json
{ "reply": "Bisa tolong sebutkan nama proyeknya?" }
```
`create_project` hanya ditawarkan bila user punya permission `create project`.

### Error
| Status | Body |
|---|---|
| `401` | token invalid |
| `422` | validasi `{ "message", "errors" }` |
| `429` | kena rate limit |
| `502` | `{ "error": "AI Assistant sedang tidak tersedia." }` (jalur JSON) |
| `504` | `{ "error": "AI Assistant sedang sibuk/lambat merespons. ..." }` (jalur JSON) |

**Timeout client:** set minimal **120 detik** (model di CPU; cold start bisa lama).

---

## 2. POST `/ai/execute-action`

Kirim `action.tool` & `action.args` dari respons chat **setelah user menekan konfirmasi**.
Server memvalidasi ulang & cek permission dari nol.

```json
{ "tool": "create_task", "args": { "project_name": "Aplikasi Kasir", "title": "Desain login", "description": "" } }
```
```json
{ "tool": "update_task", "args": { "task_id": 340, "field": "due_date", "value": "2026-11-27" } }
```
`tool`: `create_project` | `create_task` | `update_task`. Kirim `args` persis seperti yang diterima dari chat.

### Sukses `200`
```json
{ "message": "Proyek \"Aplikasi Kasir\" berhasil dibuat.", "project_id": 12, "url": "https://flovig.com/projects/12" }
```
```json
{ "message": "Task \"Desain login\" berhasil ditambahkan ke proyek \"Aplikasi Kasir\".",
  "project_id": 12, "task_id": 340, "url": "https://flovig.com/projects/12/tasks/340" }
```
```json
{ "message": "Deadline task \"Desain login\" diubah ke 27 Nov 2026.",
  "project_id": 12, "task_id": 340, "url": "https://flovig.com/projects/12/tasks/340" }
```
Pakai `project_id` / `task_id` untuk navigasi ke screen native (`url` = halaman web).

### Error
| Status | Kapan |
|---|---|
| `403` | tidak punya permission / limit paket proyek tercapai / task di proyek yang tidak bisa diakses (`{ "error": "..." }`) |
| `422` | args tidak valid (mis. kolom/tanggal/user tujuan tidak valid), proyek tidak ditemukan / tidak punya akses, atau nama proyek cocok >1 (`{ "error": "..." }` — tampilkan ke user) |

---

## 3. Contoh Flutter (Dio)

```dart
final res = await dio.post<ResponseBody>(
  '/ai/chat',
  data: {'messages': history},
  options: Options(
    responseType: ResponseType.stream,
    receiveTimeout: const Duration(seconds: 120),
  ),
);

final type = res.headers.value('content-type') ?? '';
if (type.startsWith('text/plain')) {
  await for (final text in res.data!.stream.cast<List<int>>().transform(utf8.decoder)) {
    setState(() => bubble.text += text);
  }
} else {
  final body = jsonDecode(await utf8.decodeStream(res.data!.stream));
  if (body['action'] != null) showConfirmCard(body['action']);
  else bubble.text = body['reply'] ?? body['error'] ?? '';
}
```
