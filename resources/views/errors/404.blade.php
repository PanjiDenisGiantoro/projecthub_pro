<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | Flovig</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes fl-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes fl-pulse-ring {
            0% { transform: scale(0.9); opacity: 0.6; }
            70% { transform: scale(1.35); opacity: 0; }
            100% { transform: scale(1.35); opacity: 0; }
        }
        @keyframes fl-pop-in {
            0% { transform: scale(0.85); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fl-dash {
            0% { stroke-dashoffset: 24; }
            100% { stroke-dashoffset: 0; }
        }
        .fl-icon-wrap { animation: fl-float 3.5s ease-in-out infinite; }
        .fl-pulse-ring {
            position: absolute; inset: 0; border-radius: 9999px;
            border: 2px solid rgb(139 92 246 / 0.5);
            animation: fl-pulse-ring 2.2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .fl-pulse-ring.delay { animation-delay: 1.1s; }
        .fl-card { animation: fl-pop-in 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
        .fl-dash-circle { stroke-dasharray: 24; animation: fl-dash 1.2s ease-out; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 flex items-center justify-center p-4">

<div class="w-full max-w-md fl-card">
    <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-10 text-center">

        <div class="relative inline-flex items-center justify-center w-24 h-24 mb-6 fl-icon-wrap">
            <span class="fl-pulse-ring"></span>
            <span class="fl-pulse-ring delay"></span>
            <div class="relative w-20 h-20 rounded-full bg-violet-50 flex items-center justify-center">
                <svg class="w-10 h-10 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="fl-dash-circle" cx="11" cy="11" r="6" stroke-width="1.8" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 20l-3.5-3.5" />
                </svg>
            </div>
        </div>

        <p class="text-6xl sm:text-7xl font-extrabold text-gray-800 tracking-tight leading-none mb-2">404</p>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-3">Halaman Tidak Ditemukan</h1>
        <p class="text-sm text-gray-500 mb-8">
            {{ $exception->getMessage() ?: 'Halaman yang Anda cari mungkin sudah dipindahkan atau tidak tersedia.' }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-medium px-6 py-2.5 rounded-lg transition-colors text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Beranda
            </a>
            <a href="javascript:history.back()"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-gray-300 text-gray-600 hover:bg-gray-50 font-medium px-6 py-2.5 rounded-lg transition-colors text-sm">
                Halaman Sebelumnya
            </a>
        </div>
    </div>
</div>

</body>
</html>
