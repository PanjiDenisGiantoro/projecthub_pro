@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('content')
<div class="max-w-2xl mx-auto py-6 space-y-5">

    {{-- Profile Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Hero / Cover --}}
        <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-blue-700 px-6 py-8">
            <div class="flex items-center gap-5">
                {{-- Avatar display --}}
                <div class="relative shrink-0">
                    @if($user->avatar)
                        <img src="{{ Storage::url($user->avatar) }}"
                             alt="{{ $user->name }}"
                             class="w-20 h-20 rounded-2xl object-cover ring-4 ring-white/25 shadow-xl">
                    @else
                        <div class="w-20 h-20 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center text-white font-bold text-2xl ring-4 ring-white/25 shadow-xl">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-white leading-tight">{{ $user->name }}</h2>
                    <p class="text-blue-200 text-sm capitalize mt-0.5">{{ \App\Support\RoleLabel::for($user->getRoleNames()->first()) }}</p>
                    <p class="text-blue-300/70 text-sm mt-0.5">{{ $user->email }}</p>
                </div>
            </div>
        </div>

        {{-- Avatar Upload --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Ubah Foto Profil</h3>

            <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                    <div class="flex-1 w-full">
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Pilih foto baru</label>
                        <input type="file" name="avatar" accept="image/*"
                               class="block w-full text-sm text-gray-500
                                      file:mr-3 file:py-2 file:px-4
                                      file:rounded-lg file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-blue-50 file:text-blue-700
                                      hover:file:bg-blue-100
                                      cursor-pointer transition-colors">
                        <p class="text-xs text-gray-400 mt-1.5">Format: JPG, PNG, GIF, WEBP &bull; Maks. 2 MB</p>
                    </div>
                    <button type="submit"
                            class="shrink-0 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-colors shadow-sm">
                        Simpan Foto
                    </button>
                </div>
            </form>

            @if($user->avatar)
            <form method="POST" action="{{ route('profile.avatar.remove') }}" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="text-xs text-red-500 hover:text-red-700 hover:underline transition-colors">
                    Hapus foto profil
                </button>
            </form>
            @endif
        </div>

        {{-- Push Notification --}}
        <div class="px-6 py-5 border-b border-gray-100" x-data="notificationToggle()" x-init="init()">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Notifikasi Push</h3>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-700">Aktifkan Notifikasi Push</p>
                    <p class="text-xs text-gray-400 mt-0.5" x-show="pushAvailable">Dapatkan notifikasi browser untuk tiket, tugas, dan aktivitas lain.</p>
                    <p class="text-xs text-red-400 mt-0.5" x-show="!pushAvailable">Browser ini tidak mendukung push notification.</p>
                </div>
                <button type="button" @click="toggle()" :disabled="loading || !pushAvailable"
                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors disabled:opacity-40"
                        :class="enabled ? 'bg-blue-600' : 'bg-gray-300'">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                          :class="enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                </button>
            </div>
        </div>

        {{-- Email Notification --}}
        <div id="email-notifications" class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Notifikasi Email</h3>
            <form method="POST" action="{{ route('profile.email-notifications') }}"
                  x-data="{ enabled: {{ $user->email_notifications_enabled ? 'true' : 'false' }} }">
                @csrf
                @method('PUT')
                <input type="hidden" name="email_notifications_enabled" :value="enabled ? 0 : 1">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Aktifkan Notifikasi Email</p>
                        <p class="text-xs text-gray-400 mt-0.5">Terima email pengingat deadline tugas dan pemberitahuan penting lainnya.</p>
                    </div>
                    <button type="submit"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors"
                            :class="enabled ? 'bg-blue-600' : 'bg-gray-300'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                              :class="enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Google Calendar --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Google Calendar</h3>
            <div class="flex items-center justify-between gap-4">
                <div>
                    @if($googleToken)
                        <p class="text-sm font-medium text-gray-700">Terhubung</p>
                        <p class="text-xs text-gray-400 mt-0.5">Anda bisa membuat meeting Google Meet dari Sprint & Milestone.</p>
                    @else
                        <p class="text-sm font-medium text-gray-700">Belum terhubung</p>
                        <p class="text-xs text-gray-400 mt-0.5">Hubungkan akun Google untuk membuat jadwal & link Google Meet otomatis.</p>
                    @endif
                </div>
                @if($googleToken)
                    <form method="POST" action="{{ route('google-calendar.disconnect') }}"
                          data-confirm-submit="Putuskan sambungan Google Calendar?" data-confirm-btn="Ya, Putuskan">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                            Putuskan Sambungan
                        </button>
                    </form>
                @else
                    <a href="{{ route('google-calendar.connect') }}" class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Hubungkan Google Calendar
                    </a>
                @endif
            </div>
        </div>

        {{-- Change Password --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Ubah Password</h3>

            <form method="POST" action="{{ route('profile.password') }}" class="space-y-3">
                @csrf
                @method('PUT')

                @error('current_password')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror

                <div>
                    <label class="block text-xs text-gray-500 mb-1.5 font-medium">Password saat ini</label>
                    <input type="password" name="current_password" required
                           class="w-full px-3.5 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Password baru</label>
                        <input type="password" name="password" required
                               class="w-full px-3.5 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-400 @enderror">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Konfirmasi password baru</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3.5 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-colors shadow-sm">
                        Simpan Password
                    </button>
                </div>
            </form>
        </div>

        {{-- User Info --}}
        <div class="px-6 py-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Informasi Akun</h3>
            <dl class="space-y-3">
                <div class="flex items-center gap-4 py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-400 w-28 shrink-0">Nama Lengkap</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $user->name }}</dd>
                </div>
                <div class="flex items-center gap-4 py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-400 w-28 shrink-0">Email</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $user->email }}</dd>
                </div>
                <div class="flex items-center gap-4 py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-400 w-28 shrink-0">Role</dt>
                    <dd>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 capitalize">
                            {{ \App\Support\RoleLabel::for($user->getRoleNames()->first()) ?: '-' }}
                        </span>
                    </dd>
                </div>
                @if($user->organizationUnit)
                <div class="flex items-center gap-4 py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-400 w-28 shrink-0">Unit Organisasi</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $user->organizationUnit->name }}</dd>
                </div>
                @endif
                @if($user->structuralLevel)
                <div class="flex items-center gap-4 py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-400 w-28 shrink-0">Level</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $user->structuralLevel->name }}</dd>
                </div>
                @endif
                <div class="flex items-center gap-4 py-2">
                    <dt class="text-sm text-gray-400 w-28 shrink-0">Status</dt>
                    <dd>
                        @if($user->is_active)
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Nonaktif
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

</div>
@endsection
