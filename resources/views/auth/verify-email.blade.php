<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email — Flovig</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 flex items-center justify-center p-4">

<div class="w-full max-w-md">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-white rounded-2xl shadow-lg mb-4">
            <img src="{{ asset('flovig_logo.webp') }}" alt="Flovig" class="w-10 h-10 object-contain">
        </div>
        <h1 class="text-2xl font-bold text-white">Flovig</h1>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-violet-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <h2 class="text-xl font-semibold text-gray-800 mb-2">Verifikasi Email Anda</h2>
        <p class="text-sm text-gray-500 mb-6">
            Kami telah mengirimkan link verifikasi ke <strong class="text-gray-700">{{ Auth::user()->email }}</strong>.
            Silakan periksa kotak masuk (atau folder spam) dan klik link tersebut untuk mengaktifkan akun Anda.
        </p>

        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full bg-violet-600 hover:bg-violet-700 text-white font-medium py-2.5 rounded-lg transition-colors text-sm">
                Kirim Ulang Link Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full text-gray-500 hover:text-gray-700 text-sm py-2">
                Keluar
            </button>
        </form>
    </div>
</div>

</body>
</html>