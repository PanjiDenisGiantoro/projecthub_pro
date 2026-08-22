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

        .fl-btn-primary { transition: background-color .15s ease; }
        .fl-btn-primary:hover { background-color: #1547b8; }

        .fl-social-btn { transition: border-color .15s ease, background-color .15s ease; }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid #1a5fe0; outline-offset: 2px;
        }
    </style>
</head>
<body class="h-full bg-white">

<div class="min-h-screen w-full grid lg:grid-cols-2" x-data="{ submitting: false }">

    {{-- Left: brand panel --}}
    <div class="hidden lg:flex text-white p-16 xl:p-24 flex-col justify-center gap-6"
         style="background: linear-gradient(135deg, #1a5fe0 0%, #1a8fdb 45%, #17c9c3 75%, #14d6b8 100%);">
        <div class="inline-flex w-fit bg-white rounded-xl px-4 py-2 mb-2">
            <img src="{{ asset('flovig_logo.png') }}" alt="Flovig" class="h-10 xl:h-12 w-auto object-contain">
        </div>
        <div>
            <h2 class="text-4xl xl:text-5xl font-bold leading-tight">Selamat datang kembali</h2>
            <div class="w-14 h-1.5 bg-white rounded-full mt-4"></div>
        </div>
        <p class="max-w-md text-blue-100 leading-relaxed text-lg">
            Satu platform untuk kelola project, tim, dan operasional Anda — Kanban board, sprint planning, tiket, dan meeting dalam satu tempat.
        </p>
        <a href="{{ route('home') }}"
           class="inline-flex w-fit items-center gap-2 px-6 py-3 rounded-full border border-white/70 text-white font-medium hover:bg-white hover:text-blue-600 transition-colors mt-2">
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

            <div>
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-800">Masuk</h1>
                <div class="w-12 h-1.5 bg-blue-600 rounded-full mt-4 mb-10"></div>
            </div>

            @if(session('status'))
                <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}"
                  x-data="{ showPassword: false }"
                  @submit="submitting = true">
                @csrf

                {{-- Email --}}
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="nama@perusahaan.com"
                           class="w-full px-3 py-2.5 border @error('email') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                               class="w-full px-3 py-2.5 pr-11 border @error('password') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                            <span class="absolute inset-0 rounded border border-gray-300 bg-white peer-checked:bg-blue-600 peer-checked:border-blue-600"></span>
                            <svg class="absolute inset-0 w-4 h-4 p-[2px] text-white opacity-0 peer-checked:opacity-100 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span class="text-gray-500 text-sm">Ingat saya selama 30 hari</span>
                    </label>
                </div>

                <button type="submit" :disabled="submitting"
                        class="fl-btn-primary w-full h-[52px] rounded-full bg-blue-600 text-white font-semibold text-sm flex items-center justify-center gap-2 disabled:opacity-80">
                    <svg x-show="submitting" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Memproses...' : 'Login'"></span>
                </button>
            </form>

            <div class="relative my-7">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                <div class="relative flex justify-center text-sm"><span class="bg-white px-2 text-gray-400">atau</span></div>
            </div>

            <a href="{{ route('login.google') }}"
               @click.prevent="submitting = true; setTimeout(() => window.location = $el.href, 900)"
               class="fl-social-btn w-full inline-flex items-center justify-center gap-2 border border-gray-200 hover:bg-gray-50 hover:border-gray-300 text-gray-600 text-base rounded-full py-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.6-.2-2.36H12v4.47h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.58-5.17 3.58-8.74z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.9l-3.87-3a7.4 7.4 0 0 1-11.02-3.9H1.06v3.1A12 12 0 0 0 12 24z"/>
                    <path fill="#FBBC05" d="M5.05 14.2a7.2 7.2 0 0 1 0-4.4v-3.1H1.06a12 12 0 0 0 0 10.6z"/>
                    <path fill="#EA4335" d="M12 4.75c1.76 0 3.34.6 4.58 1.79l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.06 6.7l3.99 3.1A7.15 7.15 0 0 1 12 4.75z"/>
                </svg>
                Masuk dengan Google
            </a>

            <div class="text-center text-sm text-gray-500 mt-7">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Daftar</a>
            </div>
        </div>
    </div>

</div>

</body>
</html>
