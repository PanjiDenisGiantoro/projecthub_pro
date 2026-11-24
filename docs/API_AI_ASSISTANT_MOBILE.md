# API AI Assistant (Runa) — Panduan Mobile

Base URL: `https://flovig.com/api` · Header: `Authorization: Bearer <token>`, `Accept: application/json`, `Content-Type: application/json`
(Login: lihat `docs/API_SUBSCRIPTION_MOBILE.md` §1.)

Runa = model AI self-hosted (Ollama, `llama3.2:1b`, jalan di CPU server). Backend sama dengan widget web
(`AiAssistantWebController`), jadi perilaku & permission identik.

| Endpoint | Fungsi | Limit |
|---|---|---|
| `POST /ai/chat` | Kirim percakapan, dapat balasan (streaming teks **atau** JSON usulan aksi) | 20 req/menit/user |
| `POST /ai/execute-action` | Eksekusi aksi yang sudah dikonfirmasi user (buat proyek / task) | 20 req/menit/user |

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

Pertanyaan daftar yang jelas ("siapa saja anggota tim", "proyek apa aja") dijawab **langsung dari database**
(bukan model), juga sebagai `text/plain` — datang sekaligus dalam <1 detik. Data dibatasi hak akses user:
daftar anggota hanya untuk yang punya permission `access users`, proyek hanya yang bisa diakses user.
Untuk pertanyaan lain, server otomatis menyertakan ringkasan tim & proyek user ke konteks model —
**app tidak perlu menyisipkan data apa pun ke `messages`**.

Kalau AI sibuk/mati di tengah jalan, status tetap `200` dan teks yang dikirim:
`AI Assistant sedang sibuk/lambat merespons. Coba lagi sesaat lagi.`

**B. `application/json`** — dipakai saat pesan user terlihat minta membuat proyek/task:

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

### Sukses `200`
```json
{ "message": "Proyek \"Aplikasi Kasir\" berhasil dibuat.", "project_id": 12, "url": "https://flovig.com/projects/12" }
```
```json
{ "message": "Task \"Desain login\" berhasil ditambahkan ke proyek \"Aplikasi Kasir\".",
  "project_id": 12, "task_id": 340, "url": "https://flovig.com/projects/12/tasks/340" }
```
Pakai `project_id` / `task_id` untuk navigasi ke screen native (`url` = halaman web).

### Error
| Status | Kapan |
|---|---|
| `403` | tidak punya permission / limit paket proyek tercapai (`{ "error": "..." }`) |
| `422` | args tidak valid, proyek tidak ditemukan / tidak punya akses, atau nama proyek cocok >1 (`{ "error": "..." }` — tampilkan ke user) |

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
