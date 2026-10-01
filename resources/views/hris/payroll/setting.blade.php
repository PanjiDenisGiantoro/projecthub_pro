@extends('layouts.app')
@section('title', 'Pengaturan Penggajian')
@section('page-title', 'Pengaturan Penggajian')

@section('content')
@php
    $optionClass = "flex items-start gap-3 rounded-xl border-2 p-4 cursor-pointer transition-all";
    $optionOn    = "border-blue-500 bg-blue-50";
    $optionOff   = "border-gray-200 bg-white hover:border-gray-300";
@endphp
<div class="max-w-6xl mx-auto pt-5 pb-6 space-y-5"
     x-data="{ logOpen: false, logLoading: false, logLoaded: false,
               openLogs() {
                   this.logOpen = true;
                   if (this.logLoaded) return;
                   this.logLoading = true;
                   fetch('{{ route('hris.payroll.setting.logs') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                       .then(r => r.text())
                       .then(html => { this.$refs.logList.innerHTML = html; this.logLoaded = true; })
                       .catch(() => { this.$refs.logList.innerHTML = '<p class=&quot;px-4 py-6 text-center text-xs text-red-400&quot;>Gagal memuat riwayat.</p>'; })
                       .finally(() => { this.logLoading = false; });
               } }"
     @keydown.escape.window="logOpen = false">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <a href="{{ route('hris.payroll.index') }}" class="text-gray-400 hover:text-gray-700" title="Kembali ke Penggajian">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pengaturan Penggajian</h1>
                <p class="text-sm text-gray-500 mt-0.5">Berlaku untuk semua payroll yang digenerate setelah disimpan. Payroll yang sudah ada tidak berubah sampai digenerate ulang.</p>
            </div>
        </div>
        <button type="button" @click="openLogs()" class="fl-btn fl-btn-secondary">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Riwayat Perubahan
            @if($logsCount)
            <span class="badge bg-gray-100 text-gray-600">{{ $logsCount }}</span>
            @endif
        </button>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    <form action="{{ route('hris.payroll.setting.save') }}" method="POST" class="fl-form"
          x-data="{
              dirty: false,
              method: @js(old('method', $setting->method)),
              scheme: @js(old('payment_scheme', $setting->payment_scheme)),
              potongAlpha: @js((bool) old('potong_alpha', $setting->potong_alpha)),
              alphaMetode: @js(old('potongan_alpha_metode', $setting->potongan_alpha_metode)),
          }"
          @input="dirty = true" @change="dirty = true" @submit="dirty = false">
        @csrf

        @if($errors->any())
        <div class="fl-alert fl-alert-error">{{ $errors->first() }}</div>
        @endif

        {{-- 1. Metode PPh 21 --}}
        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Metode PPh 21</h3>
                <p class="fl-section-desc">Cara menghitung potongan pajak bulanan karyawan.</p>
                <p class="fl-section-desc">
                    Tabel tarif TER bisa diubah di
                    <a href="{{ route('hris.master.index', ['tab' => 'tax-ter']) }}" class="font-medium text-blue-600 hover:text-blue-800">Master Data → Tarif TER</a>.
                </p>
            </div>
            <div class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="{{ $optionClass }}" :class="method === 'ter' ? '{{ $optionOn }}' : '{{ $optionOff }}'">
                        <input type="radio" name="method" value="ter" x-model="method" class="mt-1 accent-blue-600">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                TER <span class="badge bg-green-100 text-green-700 ml-1">Disarankan</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Tarif Efektif Rata-rata. Bruto bulan berjalan × tarif TER (kategori A/B/C sesuai PTKP). Wajib sejak Januari 2024 (PP 58/2023).</p>
                        </div>
                    </label>
                    <label class="{{ $optionClass }}" :class="method === 'progresif' ? '{{ $optionOn }}' : '{{ $optionOff }}'">
                        <input type="radio" name="method" value="progresif" x-model="method" class="mt-1 accent-blue-600">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Progresif (lama)</p>
                            <p class="text-xs text-gray-500 mt-1">Proyeksi gaji setahun − biaya jabatan − PTKP, kena tarif berlapis Pasal 17, lalu dibagi 12.</p>
                        </div>
                    </label>
                </div>
                <p x-show="method === 'ter'" x-cloak class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-800">
                    Payroll Desember otomatis dihitung ulang dengan metode progresif (rekonsiliasi tahunan). Selisih dengan total potongan Jan–Nov disesuaikan di payroll Desember.
                </p>
            </div>
        </section>

        {{-- 2. Skema pembayaran --}}
        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Skema Pembayaran Pajak</h3>
                <p class="fl-section-desc">Siapa yang menanggung PPh 21. Besar pajaknya tetap sama; yang berubah hanya letaknya di slip gaji.</p>
            </div>
            <div class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach([
                        'gross'    => ['Gross', 'Karyawan menanggung pajaknya sendiri.'],
                        'gross_up' => ['Gross-Up', 'Perusahaan memberi tunjangan pajak senilai PPh 21.'],
                        'net'      => ['Net', 'Perusahaan menanggung pajak sebagai biayanya sendiri.'],
                    ] as $val => [$label, $desc])
                    <label class="{{ $optionClass }}" :class="scheme === '{{ $val }}' ? '{{ $optionOn }}' : '{{ $optionOff }}'">
                        <input type="radio" name="payment_scheme" value="{{ $val }}" x-model="scheme" class="mt-1 accent-blue-600">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $label }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $desc }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                <div class="rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs text-gray-600">
                    <span class="font-semibold text-gray-700">Di slip gaji:</span>
                    <span x-show="scheme === 'gross'">PPh 21 muncul sebagai <b>potongan</b>, sehingga gaji bersih berkurang.</span>
                    <span x-show="scheme === 'gross_up'" x-cloak>Tunjangan PPh 21 muncul di <b>pendapatan</b> dan PPh 21 di <b>potongan</b>. Gaji bersih tetap, tapi bruto lebih besar.</span>
                    <span x-show="scheme === 'net'" x-cloak>PPh 21 tidak muncul di pendapatan maupun potongan karyawan, melainkan di <b>tanggungan perusahaan</b>.</span>
                    <span class="block mt-1 text-gray-400">Karyawan bukan pegawai (outsourcing/magang) selalu memakai skema gross.</span>
                </div>
            </div>
        </section>

        {{-- 3. BPJS --}}
        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">BPJS Ketenagakerjaan</h3>
                <p class="fl-section-desc">Tarif Jaminan Kecelakaan Kerja (JKK) mengikuti tingkat risiko pekerjaan dan ditanggung penuh perusahaan.</p>
            </div>
            <div class="fl-fields">
                <div>
                    <label class="fl-label" for="jkk_rate">Kelas risiko JKK</label>
                    <select id="jkk_rate" name="jkk_rate" class="fl-input @error('jkk_rate') is-invalid @enderror">
                        @foreach(\App\Models\Pph21Setting::JKK_RATES as $kelas => $rate)
                        <option value="{{ $rate }}" @selected(old('jkk_rate', $setting->jkk_rate) == $rate)>
                            Kelas {{ $kelas }} · {{ rtrim(rtrim(number_format($rate * 100, 2, ',', '.'), '0'), ',') }}%
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <p class="fl-label">Komponen lain (tetap sesuai aturan)</p>
                    <p class="text-sm text-gray-600 leading-relaxed">JHT 3,7% · JP 2% · JKM 0,3% · Kesehatan 4%</p>
                </div>
            </div>
        </section>

        {{-- 4. Potongan Alpha --}}
        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Potongan Alpha</h3>
                <p class="fl-section-desc">Hari tidak hadir tanpa keterangan selalu tercatat di payroll. Pengaturan ini menentukan apakah hari alpha ikut memotong gaji.</p>
            </div>
            <div class="space-y-3">
                <label class="fl-switch">
                    <input type="checkbox" name="potong_alpha" value="1" x-model="potongAlpha">
                    <span class="fl-switch-track"></span>
                    <span>Potong gaji untuk hari alpha</span>
                </label>

                <div x-show="potongAlpha" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="{{ $optionClass }}" :class="alphaMetode === 'proporsional' ? '{{ $optionOn }}' : '{{ $optionOff }}'">
                        <input type="radio" name="potongan_alpha_metode" value="proporsional" x-model="alphaMetode" class="mt-1 accent-blue-600">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Proporsional</p>
                            <p class="text-xs text-gray-500 mt-1">Gaji pokok ÷ hari kerja × hari alpha. Besarnya mengikuti gaji tiap karyawan.</p>
                        </div>
                    </label>
                    <label class="{{ $optionClass }}" :class="alphaMetode === 'nominal' ? '{{ $optionOn }}' : '{{ $optionOff }}'">
                        <input type="radio" name="potongan_alpha_metode" value="nominal" x-model="alphaMetode" class="mt-1 accent-blue-600">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">Nominal tetap</p>
                            <p class="text-xs text-gray-500 mt-1">Potongan per hari sama untuk semua karyawan.</p>
                        </div>
                    </label>
                </div>

                <div x-show="potongAlpha && alphaMetode === 'nominal'" x-cloak class="max-w-xs">
                    <label class="fl-label" for="potongan_alpha_nominal">Potongan per hari alpha</label>
                    <div class="fl-input-icon">
                        <span class="fl-prefix">Rp</span>
                        <input type="number" id="potongan_alpha_nominal" name="potongan_alpha_nominal" min="0" step="1000"
                               value="{{ old('potongan_alpha_nominal', $setting->potongan_alpha_nominal) }}"
                               :required="potongAlpha && alphaMetode === 'nominal'"
                               class="fl-input @error('potongan_alpha_nominal') is-invalid @enderror" placeholder="0">
                    </div>
                    @error('potongan_alpha_nominal')<p class="fl-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- 5. Komponen kena pajak --}}
        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Komponen Kena Pajak</h3>
                <p class="fl-section-desc">Komponen yang ikut dihitung sebagai bruto PPh 21. Gaji pokok selalu dihitung.</p>
            </div>
            <div class="fl-fields">
                <label class="fl-switch">
                    <input type="checkbox" checked disabled>
                    <span class="fl-switch-track"></span>
                    <span class="text-gray-400">Gaji Pokok</span>
                </label>
                @foreach([
                    'tax_tunjangan_jabatan'   => 'Tunjangan Jabatan',
                    'tax_tunjangan_transport' => 'Tunjangan Transport',
                    'tax_tunjangan_makan'     => 'Tunjangan Makan',
                ] as $field => $label)
                <label class="fl-switch">
                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $setting->$field))>
                    <span class="fl-switch-track"></span>
                    <span>{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </section>

        {{-- 6. Persetujuan --}}
        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Persetujuan Pengajuan</h3>
                <p class="fl-section-desc">Jika dimatikan, pengajuan langsung disetujui otomatis dan masuk ke payroll.</p>
            </div>
            <div class="fl-fields">
                <label class="fl-switch">
                    <input type="checkbox" name="overtime_needs_approval" value="1" @checked(old('overtime_needs_approval', $setting->overtime_needs_approval))>
                    <span class="fl-switch-track"></span>
                    <span>Lembur perlu persetujuan admin</span>
                </label>
                <label class="fl-switch">
                    <input type="checkbox" name="reimbursement_needs_approval" value="1" @checked(old('reimbursement_needs_approval', $setting->reimbursement_needs_approval))>
                    <span class="fl-switch-track"></span>
                    <span>Reimburse perlu persetujuan admin</span>
                </label>
            </div>
        </section>

        <div class="fl-actions sticky bottom-0 z-10">
            <span x-show="dirty" x-cloak class="flex items-center gap-2 text-xs text-amber-700" style="margin-right:auto">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                Ada perubahan yang belum disimpan
            </span>
            <a href="{{ route('hris.payroll.index') }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" class="fl-btn fl-btn-primary">Simpan Pengaturan</button>
        </div>
    </form>

    {{-- Riwayat perubahan: drawer kanan, isinya di-lazy load saat pertama dibuka
         (lihat logs() di PayrollSettingController). --}}
    <div x-show="logOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/40" x-transition.opacity @click="logOpen = false"></div>
        <aside class="absolute inset-y-0 right-0 w-full max-w-md bg-white shadow-xl flex flex-col" x-transition>
            <div class="flex items-center justify-between gap-2 px-4 py-3.5 border-b border-gray-100">
                <span class="font-semibold text-gray-900 text-sm">Riwayat Perubahan</span>
                <button type="button" @click="logOpen = false" class="text-gray-400 hover:text-gray-700" title="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto">
                <p x-show="logLoading" class="px-4 py-6 text-center text-xs text-gray-400">Memuat riwayat...</p>
                <div x-show="!logLoading" x-ref="logList" class="divide-y divide-gray-100"></div>
            </div>
        </aside>
    </div>
</div>
@endsection
