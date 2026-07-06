@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

@section('content')
<div class="max-w-2xl mx-auto py-6 space-y-5">

    {{-- Profile Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Hero / Cover --}}
        <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-6 py-8">
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
                    <p class="text-blue-200 text-sm capitalize mt-0.5">{{ $user->getRoleNames()->first() }}</p>
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
                            class="shrink-0 px-5 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 active:bg-blue-800 transition-colors shadow-sm">
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
                           class="w-full px-3.5 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Password baru</label>
                        <input type="password" name="password" required
                               class="w-full px-3.5 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent @error('password') border-red-400 @enderror">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Konfirmasi password baru</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3.5 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-5 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 active:bg-blue-800 transition-colors shadow-sm">
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
                            {{ $user->getRoleNames()->first() ?? '-' }}
                        </span>
                    </dd>
                </div>
                @if($user->department)
                <div class="flex items-center gap-4 py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-400 w-28 shrink-0">Departemen</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $user->department->name }}</dd>
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
