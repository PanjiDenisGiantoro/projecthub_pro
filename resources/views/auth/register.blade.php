<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Flovig</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 flex items-center justify-center p-4">

<div class="w-full max-w-xl" x-data="registerForm()">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <a href="/" class="inline-flex items-center gap-2 mb-4">
            <img src="{{ asset('logo.png') }}" alt="Flovig" class="w-10 h-10 rounded-xl object-contain">
            <span class="font-semibold text-gray-900 text-lg">Flovig</span>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Buat akun baru</h1>
        <p class="text-gray-500 text-sm mt-1">Gratis selamanya, tanpa kartu kredit</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                       placeholder="Budi Santoso"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-400 @enderror">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Kerja</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       placeholder="budi@perusahaan.com"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-400 @enderror">
            </div>

            <div>
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan</label>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required
                       placeholder="PT Maju Bersama"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('company_name') border-red-400 @enderror">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Minimal 8 karakter"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-400 @enderror">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       placeholder="Ulangi password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Package Selection --}}
            <div class="pt-2">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Pilih Paket Aplikasi
                    <span class="text-red-500 ml-0.5">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-3 -mt-2">Pilih satu atau keduanya. Dapat diubah nanti.</p>

                @error('packages')
                    <p class="text-red-500 text-xs mb-3">{{ $message }}</p>
                @enderror

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                    {{-- Task Management Card --}}
                    <label
                        class="relative flex flex-col cursor-pointer rounded-xl border-2 p-4 transition-all"
                        :class="packages.includes('task_management')
                            ? 'border-blue-500 bg-blue-50'
                            : 'border-gray-200 hover:border-gray-300 bg-white'">
                        <input type="checkbox" name="packages[]" value="task_management"
                               class="sr-only"
                               x-model="packages"
                               {{ in_array('task_management', old('packages', [])) ? 'checked' : '' }}>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                                 :class="packages.includes('task_management') ? 'bg-blue-500' : 'bg-gray-100'">
                                <svg class="w-5 h-5" :class="packages.includes('task_management') ? 'text-white' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold"
                                   :class="packages.includes('task_management') ? 'text-blue-700' : 'text-gray-800'">
                                    Task Management
                                </p>
                                <p class="text-xs mt-0.5"
                                   :class="packages.includes('task_management') ? 'text-blue-600' : 'text-gray-500'">
                                    Proyek, tugas, bug ticket, milestone
                                </p>
                            </div>
                            <div class="flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center mt-0.5"
                                 :class="packages.includes('task_management') ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                                <svg x-show="packages.includes('task_management')" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <ul class="mt-3 space-y-1" :class="packages.includes('task_management') ? 'text-blue-600' : 'text-gray-400'">
                            <li class="text-xs flex items-center gap-1.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Manajemen proyek & tugas
                            </li>
                            <li class="text-xs flex items-center gap-1.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Bug ticket & time tracking
                            </li>
                            <li class="text-xs flex items-center gap-1.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Laporan & dashboard
                            </li>
                        </ul>
                    </label>

                    {{-- HRIS Card --}}
                    <label
                        class="relative flex flex-col cursor-pointer rounded-xl border-2 p-4 transition-all"
                        :class="packages.includes('hris')
                            ? 'border-violet-500 bg-violet-50'
                            : 'border-gray-200 hover:border-gray-300 bg-white'">
                        <input type="checkbox" name="packages[]" value="hris"
                               class="sr-only"
                               x-model="packages"
                               {{ in_array('hris', old('packages', [])) ? 'checked' : '' }}>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                                 :class="packages.includes('hris') ? 'bg-violet-500' : 'bg-gray-100'">
                                <svg class="w-5 h-5" :class="packages.includes('hris') ? 'text-white' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold"
                                   :class="packages.includes('hris') ? 'text-violet-700' : 'text-gray-800'">
                                    HRIS
                                </p>
                                <p class="text-xs mt-0.5"
                                   :class="packages.includes('hris') ? 'text-violet-600' : 'text-gray-500'">
                                    SDM, absensi, penggajian, cuti
                                </p>
                            </div>
                            <div class="flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center mt-0.5"
                                 :class="packages.includes('hris') ? 'border-violet-500 bg-violet-500' : 'border-gray-300'">
                                <svg x-show="packages.includes('hris')" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <ul class="mt-3 space-y-1" :class="packages.includes('hris') ? 'text-violet-600' : 'text-gray-400'">
                            <li class="text-xs flex items-center gap-1.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Data karyawan & struktur org
                            </li>
                            <li class="text-xs flex items-center gap-1.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Absensi & jadwal kerja
                            </li>
                            <li class="text-xs flex items-center gap-1.5">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Penggajian & slip gaji
                            </li>
                        </ul>
                    </label>

                </div>

                {{-- Both hint --}}
                <div class="mt-2 flex items-center justify-center">
                    <button type="button"
                            @click="packages = ['task_management', 'hris']"
                            class="text-xs text-gray-500 hover:text-gray-700 underline decoration-dotted">
                        Aktifkan keduanya sekaligus
                    </button>
                </div>
            </div>

            <button type="submit"
                    :disabled="packages.length === 0"
                    :class="packages.length === 0
                        ? 'bg-gray-300 cursor-not-allowed text-gray-400'
                        : 'bg-blue-600 hover:bg-blue-700 text-white cursor-pointer'"
                    class="w-full font-medium py-2.5 rounded-lg transition-colors text-sm mt-2">
                Buat Akun
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Masuk</a>
        </p>
    </div>

    <p class="text-center text-xs text-gray-400 mt-4">
        Dengan mendaftar, Anda menyetujui
        <a href="#" class="underline hover:text-gray-600">Syarat & Ketentuan</a> kami.
    </p>
</div>

<script>
function registerForm() {
    return {
        packages: @json(old('packages', [])),
    }
}
</script>

</body>
</html>
