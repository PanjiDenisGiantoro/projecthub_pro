@extends('layouts.app')
@section('title', 'Pengaturan Penggajian')
@section('page-title', 'Pengaturan Penggajian')

@section('content')
<div class="max-w-6xl mx-auto pt-5 space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('hris.payroll.index') }}" class="text-gray-400 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pengaturan Penggajian</h1>
            <p class="text-sm text-gray-500 mt-0.5">Metode PPh 21, BPJS Ketenagakerjaan, potongan Alpha, dan komponen gaji kena pajak untuk perusahaan ini.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6 items-start">
    <div x-data="{ method: '{{ old('method', $setting->method) }}', scheme: '{{ old('payment_scheme', $setting->payment_scheme) }}', potongAlpha: {{ old('potong_alpha', $setting->potong_alpha) ? 'true' : 'false' }}, alphaMetode: '{{ old('potongan_alpha_metode', $setting->potongan_alpha_metode) }}' }">
    <form action="{{ route('hris.payroll.setting.save') }}" method="POST" class="space-y-4">
        @csrf

        <label class="block rounded-2xl border-2 p-5 cursor-pointer transition-all"
               :class="method === 'progresif' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
            <div class="flex items-start gap-3">
                <input type="radio" name="method" value="progresif" x-model="method" class="mt-1 accent-blue-600">
                <div>
                    <p class="font-semibold text-gray-900">Metode Progresif (Lama)</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Proyeksi gaji setahun → kurangi biaya jabatan & PTKP → kena tarif berlapis (Pasal 17).
                        Hasil setahun dibagi 12 untuk potongan bulanan.
                    </p>
                </div>
            </div>
        </label>

        <label class="block rounded-2xl border-2 p-5 cursor-pointer transition-all"
               :class="method === 'ter' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
            <div class="flex items-start gap-3">
                <input type="radio" name="method" value="ter" x-model="method" class="mt-1 accent-blue-600">
                <div>
                    <p class="font-semibold text-gray-900">Metode TER (Tarif Efektif Rata-rata)</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Wajib sejak Januari 2024 (PP 58/2023 & PMK 168/2023). Bruto bulan berjalan langsung
                        dikalikan tarif TER (kategori A/B/C sesuai status PTKP) — tanpa proyeksi setahun.
                    </p>
                    <div class="mt-3 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-800">
                        Masa pajak Desember tetap otomatis dihitung ulang pakai metode progresif
                        (rekonsiliasi tahunan) — sistem akan bandingkan total yang sudah dipotong Jan–Nov
                        dengan pajak riil setahun, lalu sesuaikan di payroll Desember.
                    </div>
                </div>
            </div>
        </label>

        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-800">
            Tabel tarif TER (Kategori A/B/C) bisa dilihat & disesuaikan di
            <a href="{{ route('hris.master.index', ['tab' => 'tax-ter']) }}" class="font-semibold underline">Master Data HRIS → Tarif TER</a>.
        </div>

        <div class="rounded-2xl border-2 border-gray-200 p-5">
            <p class="font-semibold text-gray-900">Skema Pembayaran PPh 21</p>
            <p class="text-sm text-gray-500 mt-0.5 mb-3">Siapa yang menanggung pajak penghasilan karyawan. Tarifnya tetap dihitung sesuai metode di atas — ini cuma menentukan di bagian mana nilainya muncul di slip gaji.</p>

            <div class="space-y-2.5">
                <label class="flex items-start gap-2 rounded-xl border-2 p-3 cursor-pointer transition-all"
                       :class="scheme === 'gross' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                    <input type="radio" name="payment_scheme" value="gross" x-model="scheme" class="mt-1 accent-blue-600">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Gross</p>
                        <p class="text-xs text-gray-500 mt-0.5">Karyawan tanggung penuh pajaknya sendiri. Muncul sebagai potongan di slip gaji.</p>
                    </div>
                </label>
                <label class="flex items-start gap-2 rounded-xl border-2 p-3 cursor-pointer transition-all"
                       :class="scheme === 'gross_up' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                    <input type="radio" name="payment_scheme" value="gross_up" x-model="scheme" class="mt-1 accent-blue-600">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Gross-Up</p>
                        <p class="text-xs text-gray-500 mt-0.5">Perusahaan kasih tunjangan pajak sebesar PPh 21 terutang. Muncul di Pendapatan (tunjangan) sekaligus Potongan (pajak) — gaji bersih karyawan tidak berubah, tapi bruto & DPP jadi lebih besar.</p>
                    </div>
                </label>
                <label class="flex items-start gap-2 rounded-xl border-2 p-3 cursor-pointer transition-all"
                       :class="scheme === 'net' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                    <input type="radio" name="payment_scheme" value="net" x-model="scheme" class="mt-1 accent-blue-600">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Net</p>
                        <p class="text-xs text-gray-500 mt-0.5">Perusahaan tanggung penuh pajaknya sebagai biaya perusahaan. Tidak muncul di Pendapatan maupun Potongan karyawan — masuk ke bagian Tanggungan Perusahaan.</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="rounded-2xl border-2 border-gray-200 p-5">
            <p class="font-semibold text-gray-900">BPJS Ketenagakerjaan — Kelas Risiko JKK</p>
            <p class="text-sm text-gray-500 mt-0.5 mb-3">Tarif Jaminan Kecelakaan Kerja (ditanggung penuh perusahaan) tergantung tingkat risiko pekerjaan. Komponen BPJS employer lain (JHT 3,7%, JP 2%, JKM 0,3%, Kesehatan 4%) sudah tetap sesuai aturan.</p>
            <select name="jkk_rate" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                @foreach(\App\Models\Pph21Setting::JKK_RATES as $kelas => $rate)
                <option value="{{ $rate }}" @selected(old('jkk_rate', $setting->jkk_rate) == $rate)>
                    Kelas Risiko {{ $kelas }} — {{ rtrim(rtrim(number_format($rate * 100, 2, ',', '.'), '0'), ',') }}%
                </option>
                @endforeach
            </select>
        </div>

        <div class="rounded-2xl border-2 border-gray-200 p-5">
            <p class="font-semibold text-gray-900">Potongan Alpha (Tidak Hadir Tanpa Keterangan)</p>
            <p class="text-sm text-gray-500 mt-0.5 mb-3">Jumlah hari alpha tetap dihitung & ditampilkan di setiap payroll berdasarkan absensi karyawan. Pengaturan ini hanya menentukan apakah hari alpha tersebut ikut memotong gaji atau tidak.</p>

            <label class="flex items-start gap-2 rounded-xl border-2 p-3 cursor-pointer transition-all"
                   :class="potongAlpha ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                <input type="checkbox" name="potong_alpha" value="1" x-model="potongAlpha" class="mt-1 accent-blue-600">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Potong gaji untuk hari alpha</p>
                    <p class="text-xs text-gray-500 mt-0.5">Aktif: gaji dipotong sesuai metode di bawah. Nonaktif: hari alpha tetap tercatat tapi tidak mengurangi gaji.</p>
                </div>
            </label>

            <div x-show="potongAlpha" class="mt-3 space-y-2.5">
                <label class="flex items-start gap-2 rounded-xl border-2 p-3 cursor-pointer transition-all"
                       :class="alphaMetode === 'proporsional' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                    <input type="radio" name="potongan_alpha_metode" value="proporsional" x-model="alphaMetode" class="mt-1 accent-blue-600">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Proporsional dari gaji pokok</p>
                        <p class="text-xs text-gray-500 mt-0.5">Gaji pokok ÷ hari kerja × hari alpha. Nominal potongan per hari beda-beda untuk tiap karyawan sesuai gajinya.</p>
                    </div>
                </label>
                <label class="flex items-start gap-2 rounded-xl border-2 p-3 cursor-pointer transition-all"
                       :class="alphaMetode === 'nominal' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                    <input type="radio" name="potongan_alpha_metode" value="nominal" x-model="alphaMetode" class="mt-1 accent-blue-600">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">Nominal tetap per hari</p>
                        <p class="text-xs text-gray-500 mt-0.5 mb-2">Potongan per hari alpha sama untuk semua karyawan, berapa pun gajinya.</p>
                        <div x-show="alphaMetode === 'nominal'" class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">Rp</span>
                            <input type="number" name="potongan_alpha_nominal" min="0" step="1000"
                                   value="{{ old('potongan_alpha_nominal', $setting->potongan_alpha_nominal) }}"
                                   class="w-40 text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="0" @click.stop>
                            <span class="text-xs text-gray-400">/ hari</span>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <div class="rounded-2xl border-2 border-gray-200 p-5">
            <p class="font-semibold text-gray-900">Komponen Gaji yang Dihitung Pajak</p>
            <p class="text-sm text-gray-500 mt-0.5 mb-3">Pilih komponen mana yang ikut jadi bruto pajak (PPh 21). Gaji pokok selalu dihitung.</p>

            <div class="space-y-2.5">
                <label class="flex items-center gap-2 text-sm text-gray-400">
                    <input type="checkbox" checked disabled class="accent-blue-600">
                    Gaji Pokok <span class="text-xs">(selalu kena pajak)</span>
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="tax_tunjangan_jabatan" value="1"
                           @checked(old('tax_tunjangan_jabatan', $setting->tax_tunjangan_jabatan)) class="accent-blue-600">
                    Tunjangan Jabatan
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="tax_tunjangan_transport" value="1"
                           @checked(old('tax_tunjangan_transport', $setting->tax_tunjangan_transport)) class="accent-blue-600">
                    Tunjangan Transport
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="tax_tunjangan_makan" value="1"
                           @checked(old('tax_tunjangan_makan', $setting->tax_tunjangan_makan)) class="accent-blue-600">
                    Tunjangan Makan
                </label>
            </div>
        </div>

        <div class="rounded-2xl border-2 border-gray-200 p-5">
            <p class="font-semibold text-gray-900">Persetujuan Pengajuan</p>
            <p class="text-sm text-gray-500 mt-0.5 mb-3">Tentukan apakah pengajuan Lembur & Reimburse butuh persetujuan admin dulu, atau langsung disetujui otomatis saat diajukan.</p>

            <div class="space-y-2.5">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="overtime_needs_approval" value="1"
                           @checked(old('overtime_needs_approval', $setting->overtime_needs_approval)) class="accent-blue-600">
                    Lembur perlu persetujuan admin
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="reimbursement_needs_approval" value="1"
                           @checked(old('reimbursement_needs_approval', $setting->reimbursement_needs_approval)) class="accent-blue-600">
                    Reimburse perlu persetujuan admin
                </label>
            </div>
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-xl font-semibold text-white text-sm"
                style="background:var(--hris-gradient)">
            Simpan Pengaturan
        </button>
    </form>
    </div>

    {{-- Riwayat perubahan pengaturan — kolom samping, bisa dibuka/tutup.
         Isinya di-lazy load lewat fetch() cuma pas panel ini dibuka, bukan
         ikut di-query tiap kali halaman setting dibuka (lihat logs() di
         PayrollSettingController). --}}
    <aside class="rounded-2xl border-2 border-gray-200 bg-white overflow-hidden lg:sticky lg:top-5"
           x-data="{ open: false, loading: false, loaded: false }"
           x-init="$watch('open', value => {
               if (!value || loaded) return;
               loading = true;
               fetch('{{ route('hris.payroll.setting.logs') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                   .then(r => r.text())
                   .then(html => { $refs.logList.innerHTML = html; loaded = true; })
                   .catch(() => { $refs.logList.innerHTML = '<p class=&quot;px-4 py-6 text-center text-xs text-red-400&quot;>Gagal memuat riwayat.</p>'; })
                   .finally(() => { loading = false; });
           })">
        <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between gap-2 px-4 py-3.5 text-left hover:bg-gray-50 transition-colors">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-semibold text-gray-900 text-sm">Riwayat Perubahan</span>
                @if($logsCount)
                <span class="badge bg-gray-100 text-gray-600">{{ $logsCount }}</span>
                @endif
            </span>
            <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="open" x-cloak x-transition class="border-t border-gray-100 max-h-[70vh] overflow-y-auto">
            <p x-show="loading" class="px-4 py-6 text-center text-xs text-gray-400">Memuat riwayat...</p>
            <div x-show="!loading" x-ref="logList" class="divide-y divide-gray-100"></div>
        </div>
    </aside>
    </div>
</div>
@endsection
