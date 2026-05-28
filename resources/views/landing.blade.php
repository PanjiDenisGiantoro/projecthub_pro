<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProjectHub Pro — Manajemen Project & Tim dalam Satu Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 antialiased">

{{-- ── Navbar ──────────────────────────────────────────────────────────────── --}}
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">PH</span>
            </div>
            <span class="font-semibold text-gray-900">ProjectHub Pro</span>
        </a>

        <nav class="hidden md:flex items-center gap-8 text-sm text-gray-600">
            <a href="#fitur" class="hover:text-gray-900 transition-colors">Fitur</a>
            <a href="#harga" class="hover:text-gray-900 transition-colors">Harga</a>
            <a href="{{ route('login') }}" class="hover:text-gray-900 transition-colors">Masuk</a>
        </nav>

        <a href="{{ route('register') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            Mulai Gratis
        </a>
    </div>
</header>

{{-- ── Hero ─────────────────────────────────────────────────────────────────── --}}
<section class="max-w-6xl mx-auto px-6 pt-24 pb-20 text-center">
    <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-medium px-3 py-1.5 rounded-full mb-6">
        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
        Baru: Fitur Chat Real-time sudah tersedia
    </div>

    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 leading-tight mb-6">
        Kelola Project & Tim<br>
        <span class="text-blue-600">dalam Satu Platform</span>
    </h1>

    <p class="text-xl text-gray-500 max-w-2xl mx-auto mb-10 leading-relaxed">
        ProjectHub Pro menggabungkan manajemen project, CRM, invoicing, dan komunikasi tim —
        semua yang dibutuhkan bisnis Anda untuk bergerak lebih cepat.
    </p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('register') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-xl transition-colors text-base shadow-sm">
            Daftar Gratis — Tanpa Kartu Kredit
        </a>
        <a href="{{ route('login') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-8 py-3 rounded-xl transition-colors text-base">
            Lihat Demo
        </a>
    </div>

    <p class="text-sm text-gray-400 mt-4">Sudah digunakan oleh 500+ tim di Indonesia</p>

    {{-- App preview --}}
    <div class="mt-16 relative">
        <div class="bg-gradient-to-b from-gray-50 to-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden mx-auto max-w-4xl">
            <div class="bg-gray-100 px-4 py-3 flex items-center gap-2 border-b border-gray-200">
                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                <div class="flex-1 mx-4 bg-white rounded px-3 py-1 text-xs text-gray-400 text-center">app.projecthubpro.id/dashboard</div>
            </div>
            <div class="grid grid-cols-5 h-64">
                <div class="col-span-1 bg-gray-50 border-r border-gray-200 p-4 space-y-3">
                    <div class="h-2.5 bg-gray-300 rounded w-3/4"></div>
                    <div class="space-y-2 pt-2">
                        <div class="h-2 bg-blue-200 rounded w-full"></div>
                        <div class="h-2 bg-gray-200 rounded w-5/6"></div>
                        <div class="h-2 bg-gray-200 rounded w-4/5"></div>
                        <div class="h-2 bg-gray-200 rounded w-full"></div>
                        <div class="h-2 bg-gray-200 rounded w-3/4"></div>
                    </div>
                </div>
                <div class="col-span-4 p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <div class="h-3 bg-gray-800 rounded w-32"></div>
                        <div class="h-6 bg-blue-600 rounded px-3 w-20"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-blue-50 rounded-lg p-3 space-y-2">
                            <div class="h-2 bg-blue-200 rounded w-1/2"></div>
                            <div class="h-5 bg-blue-600 rounded w-1/3"></div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 space-y-2">
                            <div class="h-2 bg-green-200 rounded w-1/2"></div>
                            <div class="h-5 bg-green-600 rounded w-1/3"></div>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-3 space-y-2">
                            <div class="h-2 bg-orange-200 rounded w-1/2"></div>
                            <div class="h-5 bg-orange-500 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-2.5">
                            <div class="w-4 h-4 bg-blue-200 rounded"></div>
                            <div class="h-2 bg-gray-300 rounded flex-1"></div>
                            <div class="h-2 bg-green-200 rounded w-12"></div>
                        </div>
                        <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-2.5">
                            <div class="w-4 h-4 bg-purple-200 rounded"></div>
                            <div class="h-2 bg-gray-300 rounded flex-1"></div>
                            <div class="h-2 bg-yellow-200 rounded w-12"></div>
                        </div>
                        <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-2.5">
                            <div class="w-4 h-4 bg-green-200 rounded"></div>
                            <div class="h-2 bg-gray-300 rounded flex-1"></div>
                            <div class="h-2 bg-blue-200 rounded w-12"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Fitur ────────────────────────────────────────────────────────────────── --}}
<section id="fitur" class="bg-gray-50 py-24">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Semua yang dibutuhkan tim Anda</h2>
            <p class="text-gray-500 text-lg max-w-xl mx-auto">Satu platform menggantikan 5 tool berbeda yang biasa Anda pakai.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['icon'=>'📋','title'=>'Manajemen Project','desc'=>'Kanban board, sprint planning, milestone tracking, dan Gantt chart dalam satu tampilan yang intuitif.','color'=>'bg-blue-50 text-blue-600'],
                ['icon'=>'✅','title'=>'Task Management','desc'=>'Assign task, set deadline, track progress, dan kelola dependensi antar task dengan mudah.','color'=>'bg-green-50 text-green-600'],
                ['icon'=>'👥','title'=>'CRM & Leads','desc'=>'Kelola kontak, pipeline penjualan, dan kampanye marketing dari satu dashboard terintegrasi.','color'=>'bg-purple-50 text-purple-600'],
                ['icon'=>'💬','title'=>'Chat Real-time','desc'=>'Diskusi per project tanpa perlu keluar dari platform. Kirim file, reaksi, dan mention anggota tim.','color'=>'bg-orange-50 text-orange-600'],
                ['icon'=>'🧾','title'=>'Invoice & Budget','desc'=>'Buat invoice profesional, track pembayaran, dan pantau anggaran project secara real-time.','color'=>'bg-pink-50 text-pink-600'],
                ['icon'=>'🐛','title'=>'Bug Tracker','desc'=>'Catat, prioritaskan, dan selesaikan bug dengan SLA tracking agar tidak ada yang terlewat.','color'=>'bg-red-50 text-red-600'],
            ] as $f)
            <div class="bg-white rounded-2xl p-6 border border-gray-100 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 {{ $f['color'] }} rounded-xl flex items-center justify-center text-2xl mb-4">
                    {{ $f['icon'] }}
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Social proof ─────────────────────────────────────────────────────────── --}}
<section class="py-20 max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-3 gap-6">
        @foreach([
            ['stat'=>'500+','label'=>'Tim aktif','sub'=>'dari berbagai industri'],
            ['stat'=>'50rb+','label'=>'Task diselesaikan','sub'=>'setiap bulannya'],
            ['stat'=>'99.9%','label'=>'Uptime','sub'=>'infrastruktur terjamin'],
        ] as $s)
        <div class="text-center">
            <div class="text-4xl font-bold text-blue-600 mb-1">{{ $s['stat'] }}</div>
            <div class="font-medium text-gray-900">{{ $s['label'] }}</div>
            <div class="text-sm text-gray-400">{{ $s['sub'] }}</div>
        </div>
        @endforeach
    </div>
</section>

{{-- ── Harga ────────────────────────────────────────────────────────────────── --}}
<section id="harga" class="bg-gray-50 py-24">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Harga yang transparan</h2>
            <p class="text-gray-500 text-lg">Mulai gratis, upgrade kapan saja.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- Free --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="text-sm font-medium text-gray-500 mb-2">Gratis</div>
                <div class="text-4xl font-bold text-gray-900 mb-1">Rp 0</div>
                <div class="text-sm text-gray-400 mb-6">Selamanya</div>
                <ul class="space-y-3 text-sm text-gray-600 mb-8">
                    @foreach(['5 project','3 anggota tim','Task & milestone','1 GB storage'] as $f)
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> {{ $f }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center border border-gray-300 hover:border-gray-400 text-gray-700 font-medium py-2.5 rounded-xl transition-colors text-sm">
                    Mulai Gratis
                </a>
            </div>

            {{-- Pro --}}
            <div class="bg-blue-600 rounded-2xl p-8 border border-blue-600 relative">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-orange-400 text-white text-xs font-bold px-3 py-1 rounded-full">POPULER</div>
                <div class="text-sm font-medium text-blue-200 mb-2">Pro</div>
                <div class="text-4xl font-bold text-white mb-1">Rp 299rb</div>
                <div class="text-sm text-blue-200 mb-6">per bulan / tim</div>
                <ul class="space-y-3 text-sm text-blue-100 mb-8">
                    @foreach(['Unlimited project','Unlimited anggota','CRM & Invoice','Bug tracker + SLA','Chat real-time','50 GB storage'] as $f)
                    <li class="flex items-center gap-2"><span class="text-white">✓</span> {{ $f }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center bg-white hover:bg-blue-50 text-blue-600 font-medium py-2.5 rounded-xl transition-colors text-sm">
                    Coba 14 Hari Gratis
                </a>
            </div>

            {{-- Enterprise --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="text-sm font-medium text-gray-500 mb-2">Enterprise</div>
                <div class="text-4xl font-bold text-gray-900 mb-1">Custom</div>
                <div class="text-sm text-gray-400 mb-6">Hubungi kami</div>
                <ul class="space-y-3 text-sm text-gray-600 mb-8">
                    @foreach(['Semua fitur Pro','SSO & LDAP','Dedicated server','SLA 99.99%','Priority support','Custom integrasi'] as $f)
                    <li class="flex items-center gap-2"><span class="text-green-500">✓</span> {{ $f }}</li>
                    @endforeach
                </ul>
                <a href="mailto:sales@projecthubpro.id" class="block text-center border border-gray-300 hover:border-gray-400 text-gray-700 font-medium py-2.5 rounded-xl transition-colors text-sm">
                    Hubungi Sales
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ── CTA ──────────────────────────────────────────────────────────────────── --}}
<section class="py-24 max-w-3xl mx-auto px-6 text-center">
    <h2 class="text-3xl font-bold text-gray-900 mb-4">Siap memulai?</h2>
    <p class="text-gray-500 text-lg mb-8">Daftar dalam 30 detik. Tidak perlu kartu kredit.</p>
    <a href="{{ route('register') }}"
       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-10 py-3.5 rounded-xl transition-colors shadow-sm text-base">
        Buat Akun Gratis
    </a>
</section>

{{-- ── Footer ───────────────────────────────────────────────────────────────── --}}
<footer class="border-t border-gray-100 py-10">
    <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-xs">PH</span>
            </div>
            <span class="text-sm font-medium text-gray-700">ProjectHub Pro</span>
        </div>
        <p class="text-sm text-gray-400">© {{ date('Y') }} ProjectHub Pro. Dibuat dengan ❤ di Indonesia.</p>
        <div class="flex gap-6 text-sm text-gray-400">
            <a href="#" class="hover:text-gray-600 transition-colors">Privasi</a>
            <a href="#" class="hover:text-gray-600 transition-colors">Syarat</a>
            <a href="{{ route('login') }}" class="hover:text-gray-600 transition-colors">Login</a>
        </div>
    </div>
</footer>

</body>
</html>
