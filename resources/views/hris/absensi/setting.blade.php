@extends('layouts.app')
@section('title', 'Pengaturan Absensi')
@section('page-title', 'Pengaturan Absensi')

@push('head')
{{-- face-api.js (Vladimir Mandic maintained fork) --}}
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
@endpush

@section('content')
<div class="space-y-6 pt-5" x-data="attendanceSetting()">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--lav-600)">Konfigurasi HRIS</p>
            <h1 class="font-display text-2xl font-extrabold" style="color:var(--fl-text-h,#1a0a3d)">Pengaturan Absensi</h1>
            <p class="text-sm mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Konfigurasi validasi lokasi dan pengenalan wajah untuk proses absensi.</p>
        </div>
        <a href="{{ route('hris.absensi.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all"
           style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Absensi
        </a>
    </div>

    <form action="{{ route('hris.absensi.setting.save') }}" method="POST" class="space-y-5">
        @csrf

        {{-- ── LOKASI SECTION ─────────────────────────────────────────── --}}
        <div class="rounded-2xl overflow-hidden border transition-all"
             style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">

            {{-- Header Toggle --}}
            <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe)">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background:rgba(16,185,129,0.12)">
                        <svg class="w-5 h-5" style="color:#10b981" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-[15px]" style="color:var(--fl-text-h,#1a0a3d)">Validasi Lokasi (GPS)</p>
                        <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Karyawan hanya bisa absen jika berada dalam radius yang ditentukan dari kantor.</p>
                    </div>
                </div>
                {{-- Toggle switch --}}
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="hidden" name="is_location_enabled" value="0">
                    <input type="checkbox" name="is_location_enabled" value="1"
                           @change="locationEnabled = $event.target.checked"
                           {{ $setting->is_location_enabled ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 rounded-full transition-all peer
                                bg-gray-200 peer-checked:bg-[#7c3aed]
                                after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                after:bg-white after:rounded-full after:h-5 after:w-5
                                after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>

            {{-- Location Config Body --}}
            <div x-show="locationEnabled" x-transition class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">Nama Kantor</label>
                        <input type="text" name="office_name" value="{{ $setting->office_name }}"
                               placeholder="cth: Kantor Pusat Jakarta"
                               class="fl-setting-input w-full px-3 py-2.5 text-sm rounded-xl border transition-all"
                               style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">Radius Maksimum</label>
                        <div class="flex items-center gap-3">
                            <input type="range" name="max_distance_meters"
                                   min="10" max="5000" step="10"
                                   value="{{ $setting->max_distance_meters }}"
                                   x-model="maxDistance"
                                   class="flex-1 accent-[#7c3aed]">
                            <span class="text-sm font-bold w-20 shrink-0" style="color:var(--lav-700,#7c3aed)">
                                <span x-text="maxDistance"></span>m
                            </span>
                        </div>
                        <p class="text-xs mt-1" style="color:var(--fl-text-subtle,#9ca3af)">Karyawan harus dalam radius ini dari koordinat kantor</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">Latitude Kantor</label>
                        <input type="number" name="office_latitude" id="office_latitude"
                               value="{{ $setting->office_latitude }}"
                               step="0.00000001" placeholder="-6.2088"
                               class="fl-setting-input w-full px-3 py-2.5 text-sm rounded-xl border transition-all"
                               style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">Longitude Kantor</label>
                        <input type="number" name="office_longitude" id="office_longitude"
                               value="{{ $setting->office_longitude }}"
                               step="0.00000001" placeholder="106.8456"
                               class="fl-setting-input w-full px-3 py-2.5 text-sm rounded-xl border transition-all"
                               style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                    </div>
                </div>

                {{-- Get current location button --}}
                <div class="flex items-center gap-3">
                    <button type="button" @click="getMyLocation()"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all"
                            style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Gunakan Lokasi Saat Ini sebagai Kantor
                    </button>
                    <span x-show="locMsg" x-text="locMsg" class="text-sm" style="color:#10b981"></span>
                </div>

                {{-- Checkout option --}}
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="hidden" name="require_location_for_checkout" value="0">
                    <input type="checkbox" name="require_location_for_checkout" value="1"
                           {{ $setting->require_location_for_checkout ? 'checked' : '' }}
                           class="w-4 h-4 rounded accent-[#7c3aed]">
                    <span class="text-sm" style="color:var(--fl-text-body,#374151)">Validasi lokasi juga berlaku saat <strong>Check-Out</strong></span>
                </label>
            </div>
        </div>

        {{-- ── FACE RECOGNITION SECTION ────────────────────────────────── --}}
        <div class="rounded-2xl overflow-hidden border transition-all"
             style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">

            {{-- Header Toggle --}}
            <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe)">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background:rgba(124,58,237,0.12)">
                        <svg class="w-5 h-5" style="color:#7c3aed" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-[15px]" style="color:var(--fl-text-h,#1a0a3d)">Pengenalan Wajah (Face Recognition)</p>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:rgba(59,130,246,0.1);color:#3b82f6;border:1px solid rgba(59,130,246,0.2)">AI / ML</span>
                        </div>
                        <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Verifikasi identitas karyawan menggunakan kamera saat absen. Berjalan di browser, tanpa data dikirim ke server eksternal.</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="hidden" name="is_face_recognition_enabled" value="0">
                    <input type="checkbox" name="is_face_recognition_enabled" value="1"
                           @change="faceEnabled = $event.target.checked"
                           {{ $setting->is_face_recognition_enabled ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 rounded-full transition-all peer
                                bg-gray-200 peer-checked:bg-[#7c3aed]
                                after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                after:bg-white after:rounded-full after:h-5 after:w-5
                                after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>

            <div x-show="faceEnabled" x-transition class="p-6 space-y-6">

                {{-- Sensitivity --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">
                            Sensitivitas Pengenalan
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" name="face_recognition_threshold"
                                   min="0.3" max="0.9" step="0.05"
                                   value="{{ $setting->face_recognition_threshold }}"
                                   x-model="threshold"
                                   class="flex-1 accent-[#7c3aed]">
                            <span class="text-sm font-bold w-12 shrink-0" style="color:var(--lav-700,#7c3aed)" x-text="threshold"></span>
                        </div>
                        <div class="flex justify-between text-[10px] mt-1" style="color:var(--fl-text-subtle,#9ca3af)">
                            <span>0.3 = Ketat</span>
                            <span>0.9 = Longgar</span>
                        </div>
                        <p class="text-xs mt-1.5 p-2 rounded-lg" style="background:rgba(124,58,237,0.06);color:var(--fl-text-muted,#6b7280)">
                            Nilai rendah = lebih ketat, risiko false-reject tinggi. Nilai tinggi = lebih longgar, risiko false-accept. <strong>Rekomendasi: 0.55</strong>
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">Opsi Tambahan</label>
                        <label class="flex items-center gap-3 cursor-pointer select-none py-2">
                            <input type="hidden" name="require_face_for_checkout" value="0">
                            <input type="checkbox" name="require_face_for_checkout" value="1"
                                   {{ $setting->require_face_for_checkout ? 'checked' : '' }}
                                   class="w-4 h-4 rounded accent-[#7c3aed]">
                            <span class="text-sm" style="color:var(--fl-text-body,#374151)">Verifikasi wajah juga berlaku saat <strong>Check-Out</strong></span>
                        </label>
                    </div>
                </div>

                {{-- Divider --}}
                <div style="border-top:1px solid var(--fl-card-border,#ede9fe)"></div>

                {{-- Employee face enrollment --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="font-semibold text-sm" style="color:var(--fl-text-h,#1a0a3d)">Pendaftaran Wajah Karyawan</p>
                            <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Daftarkan wajah setiap karyawan agar bisa dikenali saat absen.</p>
                        </div>
                        <span class="text-xs px-3 py-1 rounded-full font-semibold"
                              style="background:rgba(124,58,237,0.1);color:#7c3aed">
                            {{ $employees->whereNotNull('face_descriptor')->count() }} / {{ $employees->count() }} terdaftar
                        </span>
                    </div>

                    <div class="space-y-2">
                        @foreach($employees as $emp)
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border transition-all"
                             style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe)">
                            {{-- Avatar --}}
                            @if($emp->avatar)
                                <img src="{{ Storage::url($emp->avatar) }}" class="w-9 h-9 rounded-full object-cover shrink-0">
                            @else
                                <div class="fl-avatar w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($emp->name, 0, 2)) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold truncate" style="color:var(--fl-text-h,#1a0a3d)">{{ $emp->name }}</p>
                            </div>
                            {{-- Status badge --}}
                            @if($emp->face_descriptor)
                                <span class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                                      style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Terdaftar
                                </span>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button"
                                            @click="openEnroll({{ $emp->id }}, '{{ addslashes($emp->name) }}')"
                                            class="text-xs px-3 py-1.5 rounded-lg font-medium transition-all"
                                            style="background:rgba(124,58,237,0.1);color:#7c3aed;border:1px solid rgba(124,58,237,0.2)">
                                        Perbarui
                                    </button>
                                    <form action="{{ route('hris.absensi.delete-face', $emp) }}" method="POST" class="inline"
                                          data-confirm-delete="{{ $emp->name }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg font-medium transition-all"
                                                style="background:rgba(239,68,68,0.08);color:#ef4444;border:1px solid rgba(239,68,68,0.15)">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                                      style="background:rgba(245,158,11,0.1);color:#f59e0b;border:1px solid rgba(245,158,11,0.2)">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Belum Terdaftar
                                </span>
                                <button type="button"
                                        @click="openEnroll({{ $emp->id }}, '{{ addslashes($emp->name) }}')"
                                        class="text-xs px-3 py-1.5 rounded-lg font-semibold shrink-0 transition-all"
                                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;box-shadow:0 2px 8px rgba(109,40,217,0.3)">
                                    Daftarkan Wajah
                                </button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Save button --}}
        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-sm text-white transition-all hover:-translate-y-0.5"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);box-shadow:0 4px 16px rgba(109,40,217,0.35)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- ── Face Enrollment Modal ──────────────────────────────────────────── --}}
<div x-show="enrollOpen" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.7);backdrop-filter:blur(4px)">
    <div class="relative w-full max-w-md rounded-2xl overflow-hidden shadow-2xl"
         style="background:var(--fl-card-bg,#fff);border:1px solid var(--fl-card-border,#ede9fe)"
         @click.stop>

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe)">
            <div>
                <p class="font-semibold" style="color:var(--fl-text-h,#1a0a3d)">Daftarkan Wajah</p>
                <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)" x-text="'Karyawan: ' + enrollName"></p>
            </div>
            <button @click="closeEnroll()" class="p-1.5 rounded-lg transition-all" style="color:var(--fl-text-muted,#6b7280)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Camera area --}}
        <div class="p-6 space-y-4">
            <div class="relative rounded-xl overflow-hidden bg-black" style="aspect-ratio:4/3">
                <video id="enroll-video" autoplay muted playsinline
                       class="w-full h-full object-cover" style="transform:scaleX(-1)"></video>
                <canvas id="enroll-overlay" class="absolute inset-0 w-full h-full" style="transform:scaleX(-1)"></canvas>

                {{-- Overlay: loading models --}}
                <div x-show="enrollStatus === 'loading'"
                     class="absolute inset-0 flex flex-col items-center justify-center"
                     style="background:rgba(0,0,0,0.6)">
                    <svg class="w-8 h-8 animate-spin text-white mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <p class="text-white text-sm">Memuat model AI...</p>
                </div>

                {{-- Success overlay --}}
                <div x-show="enrollStatus === 'done'"
                     class="absolute inset-0 flex flex-col items-center justify-center"
                     style="background:rgba(16,185,129,0.2);backdrop-filter:blur(2px)">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3"
                         style="background:rgba(16,185,129,0.9)">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="text-white font-bold text-lg">Wajah Terdaftar!</p>
                </div>

                {{-- Face detection guide --}}
                <div x-show="enrollStatus === 'ready'"
                     class="absolute bottom-3 left-0 right-0 text-center">
                    <span class="text-xs px-3 py-1 rounded-full text-white" style="background:rgba(0,0,0,0.5)"
                          x-text="captureCount > 0 ? captureCount + '/3 frame diambil...' : 'Hadapkan wajah ke kamera'"></span>
                </div>
            </div>

            {{-- Info --}}
            <div class="text-xs p-3 rounded-xl" style="background:rgba(124,58,237,0.06);color:var(--fl-text-muted,#6b7280)">
                <strong style="color:#7c3aed">Cara pendaftaran:</strong> Hadapkan wajah ke kamera dengan pencahayaan yang baik. Sistem akan mengambil 3 frame dan menghitung descriptor wajah rata-rata.
            </div>

            <div class="flex gap-3">
                <button type="button" @click="captureEnroll()"
                        :disabled="enrollStatus !== 'ready' || captureCount >= 3"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-sm transition-all disabled:opacity-40"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;box-shadow:0 4px 12px rgba(109,40,217,0.3)">
                    <span x-text="captureCount < 3 ? 'Ambil Frame (' + captureCount + '/3)' : 'Menyimpan...'"></span>
                </button>
                <button type="button" @click="closeEnroll()"
                        class="px-5 py-2.5 rounded-xl font-medium text-sm transition-all"
                        style="background:var(--fl-search-bg,#f5f3ff);color:var(--fl-text-muted,#6b7280);border:1px solid var(--fl-card-border,#ede9fe)">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function attendanceSetting() {
    return {
        locationEnabled: {{ $setting->is_location_enabled ? 'true' : 'false' }},
        faceEnabled:     {{ $setting->is_face_recognition_enabled ? 'true' : 'false' }},
        maxDistance:     {{ $setting->max_distance_meters }},
        threshold:       {{ $setting->face_recognition_threshold }},
        locMsg:          '',
        enrollOpen:      false,
        enrollId:        null,
        enrollName:      '',
        enrollStatus:    'idle', // idle | loading | ready | capturing | done | error
        captureCount:    0,
        capturedDescs:   [],
        enrollStream:    null,
        faceApiLoaded:   false,
        detectionLoop:   null,

        async getMyLocation() {
            if (!navigator.geolocation) { this.locMsg = 'GPS tidak tersedia'; return; }
            this.locMsg = 'Mendeteksi...';
            navigator.geolocation.getCurrentPosition(
                pos => {
                    document.getElementById('office_latitude').value  = pos.coords.latitude.toFixed(8);
                    document.getElementById('office_longitude').value = pos.coords.longitude.toFixed(8);
                    this.locMsg = `✓ Lokasi ditetapkan (±${Math.round(pos.coords.accuracy)}m akurasi)`;
                },
                () => { this.locMsg = 'Gagal mendapatkan lokasi.'; }
            );
        },

        async openEnroll(empId, empName) {
            this.enrollId     = empId;
            this.enrollName   = empName;
            this.enrollOpen   = true;
            this.enrollStatus = 'loading';
            this.captureCount = 0;
            this.capturedDescs= [];

            await this.$nextTick();
            await this.loadFaceModels();
            await this.startCamera('enroll-video');
            this.enrollStatus = 'ready';
            this.runDetectionLoop();
        },

        closeEnroll() {
            clearInterval(this.detectionLoop);
            if (this.enrollStream) {
                this.enrollStream.getTracks().forEach(t => t.stop());
                this.enrollStream = null;
            }
            this.enrollOpen   = false;
            this.enrollStatus = 'idle';
        },

        async loadFaceModels() {
            if (this.faceApiLoaded) return;
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
            ]);
            this.faceApiLoaded = true;
        },

        async startCamera(videoId) {
            try {
                this.enrollStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 640, height: 480, facingMode: 'user' }
                });
                const video = document.getElementById(videoId);
                video.srcObject = this.enrollStream;
                await new Promise(r => video.onloadedmetadata = r);
            } catch(e) {
                this.enrollStatus = 'error';
            }
        },

        runDetectionLoop() {
            const video   = document.getElementById('enroll-video');
            const canvas  = document.getElementById('enroll-overlay');
            clearInterval(this.detectionLoop);
            this.detectionLoop = setInterval(async () => {
                if (!video || video.readyState < 2) return;
                const det = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.4 }))
                    .withFaceLandmarks(true);
                const ctx = canvas.getContext('2d');
                canvas.width  = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (det) {
                    const box = det.detection.box;
                    ctx.strokeStyle = '#10b981';
                    ctx.lineWidth = 3;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);
                    ctx.fillStyle = 'rgba(16,185,129,0.2)';
                    ctx.fillRect(box.x, box.y, box.width, box.height);
                }
            }, 150);
        },

        async captureEnroll() {
            if (this.enrollStatus !== 'ready' || this.captureCount >= 3) return;
            const video = document.getElementById('enroll-video');
            const det   = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.5 }))
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!det) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'warning',
                    title: 'Wajah tidak terdeteksi. Pastikan wajah terlihat jelas.',
                    showConfirmButton: false, timer: 2500,
                    background: '#d97706', color: '#fff', iconColor: '#fff' });
                return;
            }

            this.capturedDescs.push(Array.from(det.descriptor));
            this.captureCount++;

            if (this.captureCount === 3) {
                await this.saveEnrollment();
            }
        },

        async saveEnrollment() {
            // Average the 3 descriptors
            const avg = this.capturedDescs[0].map((_, i) =>
                (this.capturedDescs[0][i] + this.capturedDescs[1][i] + this.capturedDescs[2][i]) / 3
            );

            clearInterval(this.detectionLoop);
            this.enrollStatus = 'loading';

            try {
                const resp = await fetch(`/hris/absensi/enroll-face/${this.enrollId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ descriptor: JSON.stringify(avg) }),
                });
                const data = await resp.json();
                if (resp.ok) {
                    this.enrollStatus = 'done';
                    setTimeout(() => { this.closeEnroll(); location.reload(); }, 1800);
                } else {
                    throw new Error(data.message || 'Gagal menyimpan.');
                }
            } catch(e) {
                this.enrollStatus = 'error';
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.message, confirmButtonColor: '#7c3aed' });
            }
        },
    };
}
</script>
@endpush
