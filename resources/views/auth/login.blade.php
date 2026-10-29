<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Flovig</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }

        /* Latar navy bertitik */
        .lgn-stage {
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px 16px;
            background-color: #050d2b;
            background-image:
                radial-gradient(rgba(120, 160, 255, .35) 1px, transparent 1.5px),
                radial-gradient(ellipse at 15% 20%, rgba(40, 90, 220, .35), transparent 55%),
                radial-gradient(ellipse at 85% 85%, rgba(40, 90, 220, .30), transparent 55%);
            background-size: 14px 14px, 100% 100%, 100% 100%;
        }

        .lgn-card {
            width: 100%; max-width: 1080px; background: #fff; border-radius: 22px; padding: 14px;
            display: grid; grid-template-columns: 1fr; box-shadow: 0 30px 80px rgba(0, 0, 0, .45);
        }
        @media (min-width: 1024px) { .lgn-card { grid-template-columns: 1fr 1fr; min-height: 640px; } }

        /* Panel kiri */
        .lgn-hero {
            position: relative; overflow: hidden; border-radius: 16px; color: #fff; display: none;
            background: linear-gradient(160deg, #2f7bff 0%, #1a5fe0 55%, #1550c8 100%);
        }
        @media (min-width: 1024px) { .lgn-hero { display: flex; flex-direction: column; } }
        .lgn-hero::before {
            content: ''; position: absolute; inset: 0; opacity: .18;
            background-image: radial-gradient(rgba(255, 255, 255, .6) .6px, transparent 1px); background-size: 6px 6px;
        }
        .lgn-hero-top { position: relative; flex: 1; min-height: 380px; }
        .lgn-globe { position: absolute; left: 50%; bottom: -70px; transform: translateX(-50%); width: 78%; max-width: 360px; }
        .lgn-links { position: absolute; inset: 0; width: 100%; height: 100%; }
        .lgn-chip {
            position: absolute; width: 54px; height: 54px; border-radius: 14px; background: #fff; display: flex;
            align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(5, 20, 70, .35);
            border: 3px solid rgba(255, 255, 255, .7); animation: lgn-float 5s ease-in-out infinite;
        }
        .lgn-chip svg { width: 26px; height: 26px; }
        @keyframes lgn-float { 0%, 100% { transform: translateY(0) rotate(var(--r, 0deg)); } 50% { transform: translateY(-7px) rotate(var(--r, 0deg)); } }

        .lgn-quote {
            position: relative; padding: 28px 30px 26px; border-top: 1px solid rgba(255, 255, 255, .25);
            background: linear-gradient(180deg, rgba(255, 255, 255, .16), rgba(255, 255, 255, .06));
            backdrop-filter: blur(8px);
        }
        .lgn-nav {
            width: 40px; height: 40px; border-radius: 999px; border: 1px solid rgba(255, 255, 255, .6);
            display: inline-flex; align-items: center; justify-content: center; transition: background-color .15s ease;
        }
        .lgn-nav:hover { background: rgba(255, 255, 255, .15); }

        /* Panel kanan */
        .lgn-form-side {
            position: relative; display: flex; align-items: center; justify-content: center; padding: 40px 20px;
            background-image:
                linear-gradient(rgba(15, 23, 42, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, .045) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: none;
        }
        .lgn-form-side::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(ellipse at 50% 45%, transparent 0%, #fff 75%);
        }
        .lgn-form { position: relative; z-index: 1; width: 100%; max-width: 340px; }
        .lgn-avatar {
            width: 64px; height: 64px; border-radius: 999px; margin: 0 auto 26px; background: #fff; color: #374151;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 10px #f5f6f8, 0 2px 6px rgba(15, 23, 42, .08);
        }
        .lgn-input {
            width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; color: #111827;
            background: #fff; transition: border-color .15s ease, box-shadow .15s ease;
        }
        .lgn-input::placeholder { color: #9ca3af; }
        .lgn-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
        .lgn-input.is-invalid { border-color: #f87171; }
        .lgn-label { display: block; font-size: 12px; font-weight: 500; color: #374151; margin-bottom: 6px; }

        .fl-btn-primary { transition: background-color .15s ease; }
        .fl-btn-primary:hover { background-color: #1547b8; }
        .fl-social-btn { transition: border-color .15s ease, background-color .15s ease; }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid #1a5fe0; outline-offset: 2px;
        }
    </style>
</head>
<body class="h-full">

<div class="lgn-stage" x-data="{ submitting: false }">
    <div class="lgn-card">

        {{-- Kiri: panel brand --}}
        <div class="lgn-hero"
             x-data="{
                i: 0,
                slides: [
                    { quote: 'Kelola project, tim, dan operasional dalam satu tempat.', title: 'Project Management', sub: 'Kanban board & sprint planning' },
                    { quote: 'Diskusi tim lebih cepat dengan chat, forum, dan meeting terintegrasi.', title: 'Kolaborasi Tim', sub: 'Chat proyek, forum & direct message' },
                    { quote: 'Pantau tiket, absensi, cuti, dan reimburse tanpa pindah aplikasi.', title: 'Operasional & HRIS', sub: 'Tiket, absensi & pengajuan' },
                ],
                next() { this.i = (this.i + 1) % this.slides.length },
                prev() { this.i = (this.i - 1 + this.slides.length) % this.slides.length },
             }"
             x-init="setInterval(() => next(), 6000)">

            <div class="lgn-hero-top">
                <div style="top:26px; left:26px" class="absolute z-10 inline-flex items-center bg-white rounded-lg px-3 py-1.5">
                    <img src="{{ asset('flovig_logo.png') }}" alt="Flovig" class="h-6 w-auto object-contain">
                </div>

                {{-- Garis penghubung ikon ke globe --}}
                <svg class="lgn-links" viewBox="0 0 400 400" preserveAspectRatio="none" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="1.2">
                    <path d="M118 112 C 105 170, 150 200, 175 250"/>
                    <path d="M290 98 C 300 150, 265 190, 240 245"/>
                    <path d="M70 215 C 110 215, 125 245, 150 275"/>
                    <path d="M335 230 C 300 230, 280 255, 262 285"/>
                </svg>

                {{-- Globe --}}
                <svg class="lgn-globe" viewBox="0 0 300 300" aria-hidden="true">
                    <defs>
                        <radialGradient id="flGlobeFill" cx="40%" cy="32%" r="75%">
                            <stop offset="0%" stop-color="#cfe4ff"/>
                            <stop offset="45%" stop-color="#7fb2ff"/>
                            <stop offset="100%" stop-color="#2a63d6"/>
                        </radialGradient>
                        <radialGradient id="flGlobeGlow" cx="50%" cy="50%" r="50%">
                            <stop offset="70%" stop-color="#ffffff" stop-opacity=".22"/>
                            <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                        </radialGradient>
                        <clipPath id="flGlobeClip"><circle cx="150" cy="150" r="130"/></clipPath>
                    </defs>
                    <circle cx="150" cy="150" r="148" fill="url(#flGlobeGlow)"/>
                    <circle cx="150" cy="150" r="130" fill="url(#flGlobeFill)"/>
                    <g clip-path="url(#flGlobeClip)" fill="none" stroke="#ffffff" stroke-opacity=".35" stroke-width="1">
                        <ellipse cx="150" cy="150" rx="130" ry="30"/>
                        <ellipse cx="150" cy="150" rx="130" ry="70"/>
                        <ellipse cx="150" cy="150" rx="130" ry="108"/>
                        <line x1="20" y1="150" x2="280" y2="150"/>
                        <ellipse cx="150" cy="150" rx="30" ry="130"/>
                        <ellipse cx="150" cy="150" rx="70" ry="130"/>
                        <ellipse cx="150" cy="150" rx="108" ry="130"/>
                        <line x1="150" y1="20" x2="150" y2="280"/>
                    </g>
                    <g clip-path="url(#flGlobeClip)" fill="#ffffff" fill-opacity=".85">
                        <circle cx="178" cy="250" r="9" fill="#1a5fe0" stroke="#fff" stroke-width="3"/>
                        <circle cx="92" cy="232" r="7" fill="#1a5fe0" stroke="#fff" stroke-width="3"/>
                        <circle cx="228" cy="215" r="6" fill="#1a5fe0" stroke="#fff" stroke-width="3"/>
                    </g>
                </svg>

                {{-- Ikon modul melayang --}}
                <div class="lgn-chip" style="left:24%; top:20%; --r:-6deg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#1a5fe0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M15 3v12"/></svg>
                </div>
                <div class="lgn-chip" style="left:66%; top:17%; --r:8deg; animation-delay:-1.2s">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="lgn-chip" style="left:8%; top:47%; --r:4deg; animation-delay:-2.4s">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
                <div class="lgn-chip" style="left:77%; top:51%; --r:-8deg; animation-delay:-3.6s">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
                </div>
            </div>

            <div class="lgn-quote">
                <p class="text-xl font-semibold leading-snug" style="min-height:64px; font-size:22px" x-text="'“' + slides[i].quote + '”'"></p>
                <div class="flex items-end justify-between mt-6">
                    <div>
                        <p class="text-lg font-semibold" x-text="slides[i].title"></p>
                        <p class="text-sm text-blue-100 mt-1" x-text="slides[i].sub"></p>
                        <div class="flex gap-1.5 mt-3">
                            <template x-for="(s, idx) in slides" :key="idx">
                                <span class="h-1.5 rounded-full transition-all" :class="idx === i ? 'w-5 bg-white' : 'w-1.5 bg-white/50'"></span>
                            </template>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" class="lgn-nav" @click="prev()" aria-label="Sebelumnya">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6-6-6 6 6 6"/></svg>
                        </button>
                        <button type="button" class="lgn-nav" @click="next()" aria-label="Berikutnya">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: form login --}}
        <div class="lgn-form-side">
            <div class="lgn-form">

                {{-- Logo mobile --}}
                <div class="lg:hidden flex items-center justify-center mb-6">
                    <img src="{{ asset('flovig_logo.png') }}" alt="Flovig" class="h-8 w-auto object-contain">
                </div>

                <div class="lgn-avatar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0"/></svg>
                </div>

                <h1 class="text-2xl font-semibold text-gray-900">Masuk ke Akun</h1>
                <p class="text-sm text-gray-500 mt-1.5 mb-6 leading-relaxed">Selamat datang kembali! Masukkan email dan password Anda untuk melanjutkan.</p>

                @if(session('status'))
                    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg px-3 py-2.5 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2.5 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}"
                      x-data="{ showPassword: false }"
                      @submit="submitting = true">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-4">
                        <label for="email" class="lgn-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="nama@perusahaan.com"
                               class="lgn-input @error('email') is-invalid @enderror">
                        @error('email')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
                        <label for="password" class="lgn-label">Password</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                   placeholder="Masukkan password"
                                   style="padding-right:38px" class="lgn-input @error('password') is-invalid @enderror">
                            <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.592m3.213-2.05A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.965 9.965 0 01-1.563 3.029m-5.858.908a3 3 0 11-4.243-4.243M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center mb-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="relative w-4 h-4 shrink-0">
                                <input type="checkbox" name="remember" class="peer absolute inset-0 opacity-0 cursor-pointer z-10">
                                <span class="absolute inset-0 rounded border border-gray-300 bg-white peer-checked:bg-blue-600 peer-checked:border-blue-600"></span>
                                <svg class="absolute inset-0 w-4 h-4 p-[2px] text-white opacity-0 peer-checked:opacity-100 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <span class="text-gray-500 text-xs">Ingat saya selama 30 hari</span>
                        </label>
                    </div>

                    <button type="submit" :disabled="submitting"
                            class="fl-btn-primary w-full h-11 rounded-lg bg-blue-600 text-white font-medium text-sm flex items-center justify-center gap-2 disabled:opacity-80">
                        <svg x-show="submitting" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Memproses...' : 'Login'"></span>
                    </button>
                </form>

                <div class="text-center text-sm text-gray-500 mt-5">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Daftar</a>
                </div>

                <div class="relative" style="margin:20px 0">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                    <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-gray-400">ATAU</span></div>
                </div>

                <a href="{{ route('login.google') }}"
                   @click.prevent="submitting = true; setTimeout(() => window.location = $el.href, 900)"
                   class="fl-social-btn w-full inline-flex items-center justify-center gap-2 border border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 text-gray-700 text-sm rounded-lg h-11">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.6-.2-2.36H12v4.47h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.58-5.17 3.58-8.74z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.9l-3.87-3a7.4 7.4 0 0 1-11.02-3.9H1.06v3.1A12 12 0 0 0 12 24z"/>
                        <path fill="#FBBC05" d="M5.05 14.2a7.2 7.2 0 0 1 0-4.4v-3.1H1.06a12 12 0 0 0 0 10.6z"/>
                        <path fill="#EA4335" d="M12 4.75c1.76 0 3.34.6 4.58 1.79l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.06 6.7l3.99 3.1A7.15 7.15 0 0 1 12 4.75z"/>
                    </svg>
                    Masuk dengan Google
                </a>
            </div>
        </div>

    </div>
</div>

</body>
</html>
