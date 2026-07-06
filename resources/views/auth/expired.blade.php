<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masa Aktif Berakhir — Flovig</title>
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
        <p class="text-blue-200 text-sm mt-1">Integrated Project & Marketing Managements</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="flex items-center justify-center w-14 h-14 rounded-full bg-red-50 mx-auto mb-4">
            <svg class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
        </div>

        <h2 class="text-lg font-semibold text-gray-900 text-center mb-1">Masa Aktif Akun Telah Berakhir</h2>
        <p class="text-sm text-gray-500 text-center mb-5">
            @if($email)
                Masa uji coba/aktif untuk <span class="font-medium text-gray-700">{{ $email }}</span>
            @else
                Masa uji coba/aktif akun perusahaan Anda
            @endif
            @if($activeUntil)
                telah berakhir sejak {{ \Carbon\Carbon::parse($activeUntil)->translatedFormat('d M Y') }}.
            @else
                telah berakhir.
            @endif
        </p>

        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 text-sm mb-6">
            Semua data perusahaan (proyek, tugas, absensi, dll) tetap tersimpan untuk sementara waktu. Segera lakukan
            pembayaran untuk mengaktifkan kembali akun, sebelum data Anda dihapus permanen.
        </div>

        <a href="mailto:sales@projecthubpro.id?subject={{ urlencode('Perpanjangan Akun Flovig' . ($email ? ' - '.$email : '')) }}"
           class="w-full inline-flex items-center justify-center bg-violet-600 hover:bg-violet-700 text-white font-medium py-2.5 rounded-lg transition-colors text-sm">
            Hubungi Kami untuk Pembayaran
        </a>

        <p class="text-center text-sm text-gray-500 mt-5">
            Sudah melakukan pembayaran?
            <a href="{{ route('login') }}" class="text-violet-600 hover:underline font-medium">Coba masuk lagi</a>
        </p>
    </div>
</div>

</body>
</html>
