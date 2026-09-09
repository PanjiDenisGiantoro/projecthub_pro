@extends('layouts.app')
@section('title', 'Integrasi API Absensi')
@section('page-title', 'Integrasi API Absensi')

@section('content')
<div class="space-y-6 pt-5" x-data="attendanceApiIntegration()">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--lav-600)">Konfigurasi HRIS</p>
            <h1 class="font-display text-2xl font-extrabold" style="color:var(--fl-text-h,#1a0a3d)">Integrasi API Absensi</h1>
            <p class="text-sm mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Tarik data absensi dari sumber eksternal (mesin fingerprint, HRIS lain) lewat API GET/POST, lalu masukkan ke data absensi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('hris.absensi.setting') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all"
               style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Pengaturan
            </a>
            <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all"
                    style="background:#7c3aed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Sumber API
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">
        <ul class="list-disc pl-4">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- ── DAFTAR SUMBER API ──────────────────────────────────────────── --}}
    @if($sources->isEmpty())
    <div class="rounded-2xl border p-10 text-center" style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">
        <p class="text-sm" style="color:var(--fl-text-muted,#6b7280)">Belum ada sumber API absensi. Klik "Tambah Sumber API" untuk menghubungkan mesin fingerprint atau HRIS eksternal.</p>
    </div>
    @endif

    <div class="space-y-4">
        @foreach($sources as $source)
        <div class="rounded-2xl border overflow-hidden" style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)"
             x-data="sourcePanel({{ $source->id }})">

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe)">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-[15px]" style="color:var(--fl-text-h,#1a0a3d)">{{ $source->name }}</p>
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-full"
                              style="background:{{ $source->method === 'post' ? 'rgba(59,130,246,0.12)' : 'rgba(16,185,129,0.12)' }};color:{{ $source->method === 'post' ? '#3b82f6' : '#10b981' }}">
                            {{ strtoupper($source->method) }}
                        </span>
                        @if(!$source->is_active)
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Nonaktif</span>
                        @endif
                        @if($source->auto_insert)
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-full" style="background:rgba(124,58,237,0.12);color:#7c3aed">Auto Insert</span>
                        @else
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">Preview Dulu</span>
                        @endif
                    </div>
                    <p class="text-xs mt-0.5 truncate max-w-lg" style="color:var(--fl-text-muted,#6b7280)">{{ $source->url }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="panelOpen = !panelOpen"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border" style="border-color:var(--fl-card-border,#ede9fe);color:#7c3aed">
                        Tarik Data
                    </button>
                    <button type="button" @click="loadLogs()"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border" style="border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                        Riwayat ({{ $source->sync_logs_count }})
                    </button>
                    <button type="button" @click="openEdit(@js($source))"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border" style="border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                        Edit
                    </button>
                    <form action="{{ route('hris.absensi.integration.destroy', $source) }}" method="POST"
                          onsubmit="return confirm('Hapus sumber API \'{{ $source->name }}\'? Riwayat sync-nya ikut terhapus.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-200 text-red-600">Hapus</button>
                    </form>
                </div>
            </div>

            {{-- Panel tarik data --}}
            <div x-show="panelOpen" x-cloak class="px-6 py-4 space-y-4" style="background:rgba(124,58,237,0.03)">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:var(--fl-text-muted,#6b7280)">Tanggal dari</label>
                        <input type="date" x-model="dateFrom" class="rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:var(--fl-text-muted,#6b7280)">Tanggal sampai (opsional)</label>
                        <input type="date" x-model="dateTo" class="rounded-lg border-gray-300 text-sm">
                    </div>
                    <button type="button" @click="testConnection()" :disabled="loading"
                            class="px-3 py-2 rounded-lg text-xs font-medium border" style="border-color:var(--fl-card-border,#ede9fe)">
                        Test Koneksi
                    </button>
                    <button type="button" @click="preview()" :disabled="loading || !dateFrom"
                            class="px-4 py-2 rounded-lg text-xs font-semibold text-white" style="background:#7c3aed">
                        <span x-show="!loading">{{ $source->auto_insert ? 'Tarik & Simpan Otomatis' : 'Preview Data' }}</span>
                        <span x-show="loading">Memproses…</span>
                    </button>
                </div>

                <template x-if="testResult">
                    <div class="rounded-xl border p-3 text-xs font-mono overflow-auto max-h-64" style="background:#fff;border-color:var(--fl-card-border,#ede9fe)">
                        <p class="font-sans font-semibold mb-1" x-text="testResult.success ? 'Response mentah dari API:' : 'Gagal: ' + testResult.message"></p>
                        <pre x-show="testResult.success" x-text="JSON.stringify(testResult.response, null, 2)"></pre>
                    </div>
                </template>

                <template x-if="syncResult">
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="px-2 py-1 rounded-lg bg-gray-100">Ditemukan: <b x-text="syncResult.log.rows_fetched"></b></span>
                            <span class="px-2 py-1 rounded-lg bg-green-50 text-green-700">Dibuat: <b x-text="syncResult.log.rows_created"></b></span>
                            <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-700">Diperbarui: <b x-text="syncResult.log.rows_updated"></b></span>
                            <span class="px-2 py-1 rounded-lg bg-amber-50 text-amber-700">Dilewati: <b x-text="syncResult.log.rows_skipped"></b></span>
                            <span class="px-2 py-1 rounded-lg bg-red-50 text-red-700">Gagal: <b x-text="syncResult.log.rows_failed"></b></span>
                        </div>
                        <template x-if="syncResult.log.error_message">
                            <p class="text-xs text-red-600" x-text="syncResult.log.error_message"></p>
                        </template>

                        <div class="overflow-x-auto" x-show="syncResult.rows.length">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left border-b" style="border-color:var(--fl-card-border,#ede9fe)">
                                        <th class="py-1.5 pr-3">Match</th>
                                        <th class="py-1.5 pr-3">Karyawan</th>
                                        <th class="py-1.5 pr-3">Tanggal</th>
                                        <th class="py-1.5 pr-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, idx) in syncResult.rows" :key="idx">
                                        <tr class="border-b" style="border-color:var(--fl-card-border,#f5f3ff)">
                                            <td class="py-1.5 pr-3" x-text="row.match_value"></td>
                                            <td class="py-1.5 pr-3" x-text="row.employee ? row.employee.name : '—'"></td>
                                            <td class="py-1.5 pr-3" x-text="row.date"></td>
                                            <td class="py-1.5 pr-3">
                                                <span :class="resultBadgeClass(row.result)" x-text="resultLabel(row.result)"></span>
                                                <span class="text-red-500" x-show="row.error" x-text="row.error"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" x-show="!lastWasApply" @click="applyNow()" :disabled="loading"
                                class="px-4 py-2 rounded-lg text-xs font-semibold text-white" style="background:#16a34a">
                            Terapkan ke Database
                        </button>
                    </div>
                </template>
            </div>

            {{-- Panel riwayat --}}
            <div x-show="logsOpen" x-cloak class="px-6 py-4 border-t" style="border-color:var(--fl-card-border,#ede9fe)">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left border-b" style="border-color:var(--fl-card-border,#ede9fe)">
                                <th class="py-1.5 pr-3">Waktu</th>
                                <th class="py-1.5 pr-3">Mode</th>
                                <th class="py-1.5 pr-3">Rentang</th>
                                <th class="py-1.5 pr-3">Status</th>
                                <th class="py-1.5 pr-3">Dibuat/Update/Skip/Gagal</th>
                                <th class="py-1.5 pr-3">Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="log in logs" :key="log.id">
                                <tr class="border-b" style="border-color:var(--fl-card-border,#f5f3ff)">
                                    <td class="py-1.5 pr-3" x-text="log.created_at"></td>
                                    <td class="py-1.5 pr-3" x-text="log.mode"></td>
                                    <td class="py-1.5 pr-3" x-text="log.date_from + (log.date_to && log.date_to !== log.date_from ? ' – ' + log.date_to : '')"></td>
                                    <td class="py-1.5 pr-3" x-text="log.status"></td>
                                    <td class="py-1.5 pr-3" x-text="log.rows_created + '/' + log.rows_updated + '/' + log.rows_skipped + '/' + log.rows_failed"></td>
                                    <td class="py-1.5 pr-3" x-text="log.triggered_by ? log.triggered_by.name : '—'"></td>
                                </tr>
                            </template>
                            <tr x-show="!logs.length"><td colspan="6" class="py-3 text-center text-gray-400">Belum ada riwayat.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── MODAL FORM (Tambah/Edit) ──────────────────────────────────── --}}
    <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.4)">
        <div class="rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="background:var(--fl-card-bg,#fff)" @click.outside="formOpen = false">
            <form :action="editingId ? `/hris/absensi/integration/${editingId}` : '{{ route('hris.absensi.integration.store') }}'" method="POST" class="p-6 space-y-4">
                @csrf
                <template x-if="editingId"><input type="hidden" name="_method" value="PUT"></template>

                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg font-bold" style="color:var(--fl-text-h,#1a0a3d)" x-text="editingId ? 'Edit Sumber API' : 'Tambah Sumber API'"></h2>
                    <button type="button" @click="formOpen = false" class="text-gray-400">&times;</button>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1">Nama Sumber</label>
                    <input type="text" name="name" x-model="form.name" required maxlength="150" class="w-full rounded-lg border-gray-300 text-sm" placeholder="mis. Mesin Fingerprint Kantor Pusat">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Method</label>
                        <select name="method" x-model="form.method" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="get">GET</option>
                            <option value="post">POST</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Auth</label>
                        <select name="auth_type" x-model="form.auth_type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="none">Tidak ada</option>
                            <option value="api_key">API Key (header)</option>
                            <option value="bearer">Bearer Token</option>
                            <option value="basic">Basic Auth</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1">URL Endpoint</label>
                    <input type="text" name="url" x-model="form.url" required maxlength="2048" class="w-full rounded-lg border-gray-300 text-sm font-mono" placeholder="https://mesin-absensi.contoh.com/api/logs">
                </div>

                <template x-if="form.auth_type === 'api_key'">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">Nama Header</label>
                            <input type="text" name="auth_api_key_header" x-model="form.auth_api_key_header" class="w-full rounded-lg border-gray-300 text-sm" placeholder="X-API-Key">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Nilai API Key</label>
                            <input type="text" name="auth_api_key_value" x-model="form.auth_api_key_value" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>
                </template>
                <template x-if="form.auth_type === 'bearer'">
                    <div>
                        <label class="block text-xs font-medium mb-1">Bearer Token</label>
                        <input type="text" name="auth_bearer_token" x-model="form.auth_bearer_token" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </template>
                <template x-if="form.auth_type === 'basic'">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">Username</label>
                            <input type="text" name="auth_basic_username" x-model="form.auth_basic_username" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Password</label>
                            <input type="password" name="auth_basic_password" x-model="form.auth_basic_password" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>
                </template>

                <div>
                    <label class="block text-xs font-medium mb-1">
                        Format Request (JSON) — query params (GET) / body (POST)
                    </label>
                    <textarea name="request_template" x-model="form.request_template" rows="4"
                              class="w-full rounded-lg border-gray-300 text-sm font-mono"
                              placeholder='{"start_date": "@{{date_from}}", "end_date": "@{{date_to}}", "company": "@{{company_id}}"}'></textarea>
                    <p class="text-[11px] mt-1" style="color:var(--fl-text-muted,#6b7280)">
                        Placeholder yang tersedia: <code>@{{date}}</code>, <code>@{{date_from}}</code>, <code>@{{date_to}}</code>, <code>@{{company_id}}</code>. Kosongkan jika tidak perlu parameter.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1">Path array data di response (opsional)</label>
                    <input type="text" name="response_data_path" x-model="form.response_data_path" class="w-full rounded-lg border-gray-300 text-sm font-mono" placeholder="mis. data.rows — kosongkan jika response-nya langsung berupa array">
                </div>

                <div class="rounded-xl border p-4 space-y-3" style="border-color:var(--fl-card-border,#ede9fe)">
                    <p class="text-xs font-semibold" style="color:var(--fl-text-h,#1a0a3d)">Mapping Field Response → Kolom Absensi</p>
                    <p class="text-[11px]" style="color:var(--fl-text-muted,#6b7280)">
                        Klik <b>Test Koneksi</b> dulu di atas untuk melihat struktur response API, baru isi path mapping di bawah ini.
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">Cocokkan karyawan berdasarkan</label>
                            <select name="match_field" x-model="form.match_field" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="email">Email</option>
                                <option value="id">User ID (ProjectHub)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Path nilai pencocokan *</label>
                            <input type="text" name="map_match_value" x-model="form.map_match_value" required class="w-full rounded-lg border-gray-300 text-sm font-mono" placeholder="mis. employee_email">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Path tanggal *</label>
                            <input type="text" name="map_date" x-model="form.map_date" required class="w-full rounded-lg border-gray-300 text-sm font-mono" placeholder="mis. date">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Path jam masuk</label>
                            <input type="text" name="map_check_in" x-model="form.map_check_in" class="w-full rounded-lg border-gray-300 text-sm font-mono" placeholder="check_in">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Path jam keluar</label>
                            <input type="text" name="map_check_out" x-model="form.map_check_out" class="w-full rounded-lg border-gray-300 text-sm font-mono" placeholder="check_out">
                        </div>
                        <div class="hidden">
                            <label class="block text-xs font-medium mb-1">Path jam masuk sesi 2 (shift split)</label>
                            <input type="text" name="map_check_in_2" x-model="form.map_check_in_2" class="w-full rounded-lg border-gray-300 text-sm font-mono">
                        </div>
                        <div class="hidden">
                            <label class="block text-xs font-medium mb-1">Path jam keluar sesi 2 (shift split)</label>
                            <input type="text" name="map_check_out_2" x-model="form.map_check_out_2" class="w-full rounded-lg border-gray-300 text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Path status</label>
                            <input type="text" name="map_status" x-model="form.map_status" class="w-full rounded-lg border-gray-300 text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Path catatan</label>
                            <input type="text" name="map_notes" x-model="form.map_notes" class="w-full rounded-lg border-gray-300 text-sm font-mono">
                        </div>
                    </div>
                    <p class="text-[11px]" style="color:var(--fl-text-muted,#6b7280)">Path mendukung notasi titik untuk JSON bersarang, mis. <code>employee.email</code>.</p>
                </div>

                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="auto_insert" value="0">
                        <input type="checkbox" name="auto_insert" value="1" x-model="form.auto_insert" class="rounded">
                        Auto insert ke database (tanpa perlu konfirmasi preview)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="overwrite_on_conflict" value="0">
                        <input type="checkbox" name="overwrite_on_conflict" value="1" x-model="form.overwrite_on_conflict" class="rounded">
                        Timpa data absensi yang sudah ada jika bentrok (user + tanggal sama)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded">
                        Aktifkan sumber ini
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="formOpen = false" class="px-4 py-2 rounded-lg text-sm font-medium border" style="border-color:var(--fl-card-border,#ede9fe)">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#7c3aed">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function blankAttendanceApiForm() {
    return {
        name: '', method: 'get', url: '',
        auth_type: 'none', auth_api_key_header: '', auth_api_key_value: '',
        auth_bearer_token: '', auth_basic_username: '', auth_basic_password: '',
        request_template: '', response_data_path: '',
        match_field: 'email', map_match_value: '', map_date: '',
        map_check_in: '', map_check_out: '', map_check_in_2: '', map_check_out_2: '',
        map_status: '', map_notes: '',
        auto_insert: false, overwrite_on_conflict: true, is_active: true,
    };
}

function attendanceApiIntegration() {
    return {
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        formOpen: false,
        editingId: null,
        form: blankAttendanceApiForm(),
        openCreate() {
            this.editingId = null;
            this.form = blankAttendanceApiForm();
            this.formOpen = true;
        },
        openEdit(source) {
            this.editingId = source.id;
            const mapping = source.response_mapping || {};
            this.form = {
                name: source.name, method: source.method, url: source.url,
                auth_type: source.auth_type,
                auth_api_key_header: '', auth_api_key_value: '',
                auth_bearer_token: '', auth_basic_username: '', auth_basic_password: '',
                request_template: source.request_template ? JSON.stringify(source.request_template, null, 2) : '',
                response_data_path: source.response_data_path || '',
                match_field: mapping.match_field || 'email',
                map_match_value: mapping.match_value || '',
                map_date: mapping.date || '',
                map_check_in: mapping.check_in || '',
                map_check_out: mapping.check_out || '',
                map_check_in_2: mapping.check_in_2 || '',
                map_check_out_2: mapping.check_out_2 || '',
                map_status: mapping.status || '',
                map_notes: mapping.notes || '',
                auto_insert: !!source.auto_insert,
                overwrite_on_conflict: !!source.overwrite_on_conflict,
                is_active: !!source.is_active,
            };
            this.formOpen = true;
        },
    };
}

function sourcePanel(sourceId) {
    return {
        sourceId,
        panelOpen: false,
        logsOpen: false,
        loading: false,
        dateFrom: new Date().toISOString().slice(0, 10),
        dateTo: '',
        testResult: null,
        syncResult: null,
        lastWasApply: false,
        logs: [],
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

        async testConnection() {
            this.loading = true;
            this.testResult = null;
            try {
                const resp = await fetch(`/hris/absensi/integration/${this.sourceId}/test`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ date_from: this.dateFrom, date_to: this.dateTo || null }),
                });
                this.testResult = await resp.json();
            } catch (e) {
                this.testResult = { success: false, message: 'Gagal menghubungi server.' };
            }
            this.loading = false;
        },

        async preview() {
            await this.runSync(false);
        },
        async applyNow() {
            await this.runSync(true);
        },
        async runSync(apply) {
            this.loading = true;
            this.syncResult = null;
            try {
                const resp = await fetch(`/hris/absensi/integration/${this.sourceId}/sync`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ date_from: this.dateFrom, date_to: this.dateTo || null, apply: apply ? 1 : 0 }),
                });
                this.syncResult = await resp.json();
                this.lastWasApply = this.syncResult.applied;
            } catch (e) {
                this.syncResult = { log: { rows_fetched: 0, rows_created: 0, rows_updated: 0, rows_skipped: 0, rows_failed: 0, error_message: 'Gagal menghubungi server.' }, rows: [] };
            }
            this.loading = false;
        },

        async loadLogs() {
            this.logsOpen = !this.logsOpen;
            if (!this.logsOpen) return;
            try {
                const resp = await fetch(`/hris/absensi/integration/${this.sourceId}/logs`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();
                this.logs = data.logs || [];
            } catch (e) {
                this.logs = [];
            }
        },

        resultLabel(result) {
            return {
                created: 'Dibuat', updated: 'Diperbarui', skipped: 'Dilewati',
                will_create: 'Akan dibuat', will_update: 'Akan diperbarui', will_skip: 'Akan dilewati',
                error: 'Error',
            }[result] || result || '-';
        },
        resultBadgeClass(result) {
            const map = {
                created: 'text-green-700', updated: 'text-blue-700', skipped: 'text-amber-700',
                will_create: 'text-green-700', will_update: 'text-blue-700', will_skip: 'text-amber-700',
                error: 'text-red-700',
            };
            return 'font-medium ' + (map[result] || 'text-gray-500');
        },
    };
}
</script>
@endsection
