<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Flovig</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lottie-web@5.12.2/build/player/lottie.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }

        @keyframes fl-fade-up {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fl-stagger-1 { animation: fl-fade-up .6s ease-out .1s both; }
        .fl-stagger-2 { animation: fl-fade-up .6s ease-out .2s both; }
        .fl-stagger-3 { animation: fl-fade-up .6s ease-out .3s both; }
        .fl-stagger-4 { animation: fl-fade-up .6s ease-out .4s both; }
        .fl-stagger-5 { animation: fl-fade-up .6s ease-out .5s both; }

        @keyframes fl-float {
            0%, 100% { transform: translate(0, 0); }
            50%      { transform: translate(16px, -24px); }
        }
        .fl-blob { animation: fl-float 9s ease-in-out infinite; }

        @keyframes fl-ring-in {
            0%   { opacity: 0; transform: translate(-50%, -50%) scale(.5); }
            60%  { opacity: 1; transform: translate(-50%, -50%) scale(1.08); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        .fl-ring { animation: fl-ring-in .6s cubic-bezier(.34,1.56,.64,1) .3s both; }
        .fl-ring-inner {
            transition: transform .35s cubic-bezier(.22,1,.36,1);
            transform-style: preserve-3d;
            will-change: transform;
            box-shadow:
                0 30px 45px -18px rgba(15,23,42,.45),
                0 10px 18px -6px rgba(15,23,42,.18),
                inset 0 10px 16px rgba(255,255,255,.55),
                inset 0 -12px 18px rgba(15,23,42,.10);
        }
        .fl-ring-sheen {
            background: radial-gradient(circle at 30% 22%, rgba(255,255,255,.95), rgba(255,255,255,0) 46%);
            mix-blend-mode: overlay;
        }
        .fl-ring-inner img { transform: translateZ(30px); }

        @keyframes fl-shake {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }
        .fl-shake { animation: fl-shake .5s cubic-bezier(.36,.07,.19,.97) both; }

        .fl-btn-primary {
            position: relative; overflow: hidden;
            transition: filter .2s ease, transform .2s ease, box-shadow .2s ease;
        }
        .fl-btn-primary:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 12px 24px -8px rgba(37,99,235,0.5); }
        .fl-btn-primary:active { transform: translateY(0); }
        .fl-btn-primary::after {
            content: ''; position: absolute; top: 0; left: -75%; width: 50%; height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,.35), transparent);
            transform: skewX(-20deg);
        }
        .fl-btn-primary:hover::after { animation: fl-shine 1s ease forwards; }
        @keyframes fl-shine { to { left: 125%; } }

        .fl-checkbox-box { transition: background-color .2s ease, border-color .2s ease, transform .15s ease; }
        input:checked ~ .fl-checkbox-box { transform: scale(1.08); }

        .fl-social-btn { transition: border-color .2s ease, background-color .2s ease, transform .2s ease, box-shadow .2s ease; }
        .fl-social-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 16px -6px rgba(0,0,0,0.12); }

        @keyframes fl-loading-pulse {
            0%, 100% { transform: scale(1); opacity: .85; }
            50%      { transform: scale(1.12); opacity: 1; }
        }
        .fl-loading-icon { animation: fl-loading-pulse 1.1s ease-in-out infinite; }

        @keyframes fl-slide-down {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fl-error-msg { animation: fl-slide-down .25s ease-out both; }

        .fl-btn-primary { transition: filter .2s ease, transform .2s ease, box-shadow .2s ease; }
        .fl-btn-primary:hover { filter: brightness(1.08); transform: translateY(-1px); }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid #2563eb; outline-offset: 2px;
        }
    </style>
</head>
<body class="h-full bg-white">

<div class="relative min-h-screen w-full grid lg:grid-cols-2 overflow-hidden" x-data="{ submitting: false, ringRx: 0, ringRy: 0 }"
     @mousemove="const r = $el.getBoundingClientRect(); const px = (($event.clientX - r.left) / r.width) - 0.5; const py = (($event.clientY - r.top) / r.height) - 0.5; ringRy = px * 34; ringRx = -py * 34;"
     @mouseleave="ringRx = 0; ringRy = 0">

    {{-- Decorative floating blobs (Lottie), peeking from opposite corners --}}
    <div id="flovig-float-tl" class="absolute -top-16 -left-16 w-64 h-64 xl:w-80 xl:h-80 z-10 pointer-events-none"></div>
    <div id="flovig-float-br" class="absolute -bottom-16 -right-16 w-64 h-64 xl:w-80 xl:h-80 z-10 pointer-events-none"></div>

    {{-- Left: brand panel --}}
    <div class="hidden lg:flex text-white p-16 xl:p-24 flex-col justify-center gap-6 relative overflow-hidden"
         style="background: linear-gradient(135deg, #1a5fe0 0%, #1a8fdb 45%, #17c9c3 75%, #14d6b8 100%);">
        <div class="fl-blob absolute top-16 -left-16 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
        <div class="fl-blob absolute bottom-10 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl" style="animation-delay:-4s"></div>

        <div class="fl-stagger-1 relative inline-flex w-fit bg-white rounded-xl px-4 py-2 mb-2">
            <img src="{{ asset('flovig_logo.png') }}" alt="Flovig" class="h-10 xl:h-12 w-auto object-contain">
        </div>
        <div class="fl-stagger-2 relative">
            <h2 class="text-4xl xl:text-5xl font-bold leading-tight">Selamat Datang di Flovig</h2>
            <div class="w-14 h-1.5 bg-white rounded-full mt-4"></div>
        </div>
        <div class="fl-stagger-3 relative max-w-md"
             x-data="{
                msgs: [
                    'Satu platform untuk kelola project, tim, dan marketing Anda—semua jadwal, tugas, dan meeting dalam satu tempat.',
                    'Jadwalkan meeting otomatis lewat Google Calendar & Google Meet, langsung dari task, sprint, atau tiket.',
                    'Pantau progress tim lewat Kanban board, sprint planning, dan laporan real-time.',
                    'Kelola tiket bug & dukungan pelanggan dengan SLA yang otomatis terlacak.',
                    'Kolaborasi tim lebih mudah dengan chat, notifikasi, dan kalender terpadu.',
                ],
                i: 0,
             }"
             x-init="setInterval(() => i = (i + 1) % msgs.length, 4000)">
            <div class="relative min-h-[84px]">
                <template x-for="(m, idx) in msgs" :key="idx">
                    <p x-show="i === idx"
                       x-transition:enter="transition ease-out duration-500"
                       x-transition:enter-start="opacity-0 translate-y-2"
                       x-transition:enter-end="opacity-100 translate-y-0"
                       x-transition:leave="transition ease-in duration-300"
                       x-transition:leave-start="opacity-100 translate-y-0"
                       x-transition:leave-end="opacity-0 -translate-y-2"
                       class="absolute inset-0 text-blue-100 leading-relaxed text-lg"
                       x-text="m"></p>
                </template>
            </div>
            <div class="flex gap-1.5 mt-3">
                <template x-for="(m, idx) in msgs" :key="idx">
                    <button type="button" @click="i = idx" :aria-label="'Pesan ' + (idx + 1)"
                            class="h-1.5 rounded-full transition-all duration-300"
                            :class="i === idx ? 'w-6 bg-white' : 'w-1.5 bg-white/30 hover:bg-white/50'"></button>
                </template>
            </div>
        </div>
        <a href="{{ route('home') }}"
           class="fl-stagger-4 relative inline-flex w-fit items-center gap-2 px-6 py-3 rounded-full border border-white/70 text-white font-medium hover:bg-white hover:text-blue-600 transition-colors mt-2">
            Pelajari Lebih Lanjut
        </a>
    </div>

    {{-- Right: sign in form --}}
    <div class="bg-white p-8 lg:p-12 flex flex-col justify-center items-center">
        <div class="w-full max-w-md">

            {{-- Mobile logo --}}
            <div class="lg:hidden flex items-center justify-center mb-8">
                <img src="{{ asset('flovig_logo.png') }}" alt="Flovig" class="h-8 w-auto object-contain">
            </div>

            <div class="fl-stagger-1">
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-800">Masuk</h1>
                <div class="w-12 h-1.5 bg-blue-600 rounded-full mt-4 mb-10"></div>
            </div>

            @if(session('status'))
                <div class="fl-error-msg mb-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="fl-error-msg mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}"
                  x-data="{ showPassword: false }"
                  @submit="submitting = true" class="fl-stagger-2">
                @csrf

                {{-- Email --}}
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="nama@perusahaan.com"
                           class="w-full px-3 py-2.5 border @error('email') border-red-400 fl-shake @else border-gray-300 @enderror rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                               placeholder="Masukkan password"
                               class="w-full px-3 py-2.5 pr-11 border @error('password') border-red-400 fl-shake @else border-gray-300 @enderror rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPassword" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.592m3.213-2.05A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.965 9.965 0 01-1.563 3.029m-5.858.908a3 3 0 11-4.243-4.243M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center mb-9">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="relative w-4 h-4 shrink-0">
                            <input type="checkbox" name="remember" class="peer absolute inset-0 opacity-0 cursor-pointer z-10">
                            <span class="fl-checkbox-box absolute inset-0 rounded border border-gray-300 bg-white peer-checked:bg-blue-600 peer-checked:border-blue-600"></span>
                            <svg class="absolute inset-0 w-4 h-4 p-[2px] text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span class="text-gray-500 text-sm">Ingat saya selama 30 hari</span>
                    </label>
                </div>

                <button type="submit" :disabled="submitting"
                        class="fl-btn-primary w-full h-[52px] rounded-full bg-blue-600 text-white font-bold text-sm uppercase tracking-wide shadow-lg shadow-blue-200 flex items-center justify-center gap-2 disabled:opacity-80">
                    <svg x-show="submitting" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Memproses...' : 'Login'"></span>
                </button>
            </form>

            <div class="fl-stagger-3 relative my-7">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                <div class="relative flex justify-center text-sm"><span class="bg-white px-2 text-gray-400">atau</span></div>
            </div>

            <a href="{{ route('login.google') }}"
               @click.prevent="submitting = true; setTimeout(() => window.location = $el.href, 900)"
               class="fl-social-btn fl-stagger-3 w-full inline-flex items-center justify-center gap-2 border border-gray-200 hover:bg-gray-50 hover:border-gray-300 text-gray-600 text-base rounded-full py-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.6-.2-2.36H12v4.47h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.58-5.17 3.58-8.74z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.9l-3.87-3a7.4 7.4 0 0 1-11.02-3.9H1.06v3.1A12 12 0 0 0 12 24z"/>
                    <path fill="#FBBC05" d="M5.05 14.2a7.2 7.2 0 0 1 0-4.4v-3.1H1.06a12 12 0 0 0 0 10.6z"/>
                    <path fill="#EA4335" d="M12 4.75c1.76 0 3.34.6 4.58 1.79l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.06 6.7l3.99 3.1A7.15 7.15 0 0 1 12 4.75z"/>
                </svg>
                Masuk dengan Google
            </a>

            <div class="fl-stagger-4 text-center text-sm text-gray-500 mt-7">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Daftar</a>
            </div>
        </div>
    </div>

    {{-- Decorative ring straddling the seam (interactive 3D tilt) --}}
    <div class="fl-ring hidden lg:flex items-center justify-center absolute left-1/2 top-1/2 w-36 xl:w-44 h-36 xl:h-44 z-20"
         style="perspective: 900px;">
        <div class="fl-ring-inner relative w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden"
             :style="`transform: rotateX(${ringRx}deg) rotateY(${ringRy}deg);`">
            <div class="fl-ring-sheen absolute inset-0 pointer-events-none"></div>
            <img src="{{ asset('flovig_icon.png') }}" alt="" class="relative w-20 xl:w-24 h-auto object-contain drop-shadow-md">
        </div>
    </div>

    {{-- Loading overlay while login is processing --}}
    <div x-show="submitting" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-center justify-center pointer-events-none"
         style="background: rgba(15,23,42,.35); backdrop-filter: blur(4px);">
        <img src="{{ asset('flovig_icon.png') }}" alt="" class="fl-loading-icon w-16 h-16 xl:w-20 xl:h-20 object-contain drop-shadow-2xl">
    </div>
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lottie === 'undefined') return;
        ['flovig-float-tl', 'flovig-float-br'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            lottie.loadAnimation({
                container: el,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: '{{ asset('animations/flovig-float.json') }}',
                rendererSettings: { preserveAspectRatio: 'xMidYMid meet' }
            });
        });
    });
})();
</script>

</body>
</html>
