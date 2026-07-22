<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Flovig</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full bg-white">

<div class="min-h-screen grid lg:grid-cols-2">

    {{-- Left: brand panel, logo centered --}}
    <div class="relative hidden lg:flex flex-col items-center justify-center bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 p-12 text-white overflow-hidden">
        <div class="relative z-20 flex flex-col items-center gap-4 text-center">
            <div class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center shadow-xl ring-4 ring-white/20">
                <img src="{{ asset('flovig_logo.webp') }}" alt="Flovig" class="w-14 h-14 object-contain">
            </div>
            <span class="text-3xl font-bold tracking-tight">Flovig</span>
            <p class="text-lg font-medium text-white/90 max-w-xs">Integrated Project &amp; Marketing Management</p>
        </div>

        <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
    </div>

    {{-- Right: login form --}}
    <div class="flex items-center justify-center p-8 bg-white overflow-hidden">
        <div class="w-full max-w-[420px]">

            {{-- Mobile logo --}}
            <div class="lg:hidden flex items-center justify-center gap-2 text-lg font-semibold mb-6">
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <img src="{{ asset('flovig_logo.webp') }}" alt="Flovig" class="w-6 h-6 object-contain">
                </div>
                <span>Flovig</span>
            </div>

            {{-- Animated character illustration --}}
            <div class="flex items-end justify-center h-[220px] overflow-hidden">
                <div class="relative" style="width: 550px; height: 400px; transform: scale(0.5); transform-origin: bottom center;">

                    {{-- Purple character (back layer) --}}
                    <div x-data="characterSkew()" x-effect="track()"
                         class="absolute bottom-0 transition-all duration-700 ease-in-out"
                         :style="`left:70px; width:180px;
                                  height:${($store.login.isTyping || ($store.login.password.length > 0 && !$store.login.showPassword)) ? 440 : 400}px;
                                  background-color:#6C3FF5; border-radius:10px 10px 0 0; z-index:1;
                                  transform: ${
                                    ($store.login.password.length > 0 && $store.login.showPassword)
                                        ? 'skewX(0deg)'
                                        : ($store.login.isTyping || ($store.login.password.length > 0 && !$store.login.showPassword))
                                            ? `skewX(${skew - 12}deg) translateX(40px)`
                                            : `skewX(${skew}deg)`
                                  };
                                  transform-origin: bottom center;`">
                        <div class="absolute flex gap-8 transition-all duration-700 ease-in-out"
                             :style="`left:${($store.login.password.length > 0 && $store.login.showPassword) ? 20 : $store.login.lookingAtEachOther ? 55 : 45}px;
                                      top:${($store.login.password.length > 0 && $store.login.showPassword) ? 35 : $store.login.lookingAtEachOther ? 65 : 40}px;`">
                            <template x-for="i in 2" :key="i">
                                <x-eye-ball size="18" pupil-size="7" max-dist="5" character="purple" />
                            </template>
                        </div>
                    </div>

                    {{-- Black character (middle layer) --}}
                    <div x-data="characterSkew()" x-effect="track()"
                         class="absolute bottom-0 transition-all duration-700 ease-in-out"
                         :style="`left:240px; width:120px; height:310px; background-color:#2D2D2D; border-radius:8px 8px 0 0; z-index:2;
                                  transform: ${
                                    ($store.login.password.length > 0 && $store.login.showPassword)
                                        ? 'skewX(0deg)'
                                        : $store.login.lookingAtEachOther
                                            ? `skewX(${skew * 1.5 + 10}deg) translateX(20px)`
                                            : ($store.login.isTyping || ($store.login.password.length > 0 && !$store.login.showPassword))
                                                ? `skewX(${skew * 1.5}deg)`
                                                : `skewX(${skew}deg)`
                                  };
                                  transform-origin: bottom center;`">
                        <div class="absolute flex gap-6 transition-all duration-700 ease-in-out"
                             :style="`left:${($store.login.password.length > 0 && $store.login.showPassword) ? 10 : $store.login.lookingAtEachOther ? 32 : 26}px;
                                      top:${($store.login.password.length > 0 && $store.login.showPassword) ? 28 : $store.login.lookingAtEachOther ? 12 : 32}px;`">
                            <template x-for="i in 2" :key="i">
                                <x-eye-ball size="16" pupil-size="6" max-dist="4" character="black" />
                            </template>
                        </div>
                    </div>

                    {{-- Orange character (front left) --}}
                    <div x-data="characterSkew()" x-effect="track()"
                         class="absolute bottom-0 transition-all duration-700 ease-in-out"
                         :style="`left:0px; width:240px; height:200px; z-index:3; background-color:#FF9B6B; border-radius:120px 120px 0 0;
                                  transform: ${($store.login.password.length > 0 && $store.login.showPassword) ? 'skewX(0deg)' : `skewX(${skew}deg)`};
                                  transform-origin: bottom center;`">
                        <div class="absolute flex gap-8 transition-all duration-200 ease-out"
                             :style="`left:${($store.login.password.length > 0 && $store.login.showPassword) ? 50 : 82}px;
                                      top:${($store.login.password.length > 0 && $store.login.showPassword) ? 85 : 90}px;`">
                            <template x-for="i in 2" :key="i">
                                <x-eye-ball size="12" max-dist="5" character="orange" solid="true" />
                            </template>
                        </div>
                    </div>

                    {{-- Yellow character (front right) --}}
                    <div x-data="characterSkew()" x-effect="track()"
                         class="absolute bottom-0 transition-all duration-700 ease-in-out"
                         :style="`left:310px; width:140px; height:230px; background-color:#E8D754; border-radius:70px 70px 0 0; z-index:4;
                                  transform: ${($store.login.password.length > 0 && $store.login.showPassword) ? 'skewX(0deg)' : `skewX(${skew}deg)`};
                                  transform-origin: bottom center;`">
                        <div class="absolute flex gap-6 transition-all duration-200 ease-out"
                             :style="`left:${($store.login.password.length > 0 && $store.login.showPassword) ? 20 : 52}px;
                                      top:${($store.login.password.length > 0 && $store.login.showPassword) ? 35 : 40}px;`">
                            <template x-for="i in 2" :key="i">
                                <x-eye-ball size="12" max-dist="5" character="yellow" solid="true" />
                            </template>
                        </div>
                        <div class="absolute w-20 h-1 bg-[#2D2D2D] rounded-full transition-all duration-200 ease-out"
                             :style="`left:${($store.login.password.length > 0 && $store.login.showPassword) ? 10 : 40}px;
                                      top:${($store.login.password.length > 0 && $store.login.showPassword) ? 88 : 88}px;`"></div>
                    </div>

                </div>
            </div>

            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-2">Selamat Datang!</h1>
                <p class="text-gray-500 text-sm">Silakan masuk ke akun Anda</p>
            </div>

            @if(session('status'))
                <div class="mb-4 bg-violet-50 border border-violet-200 text-violet-700 rounded-lg px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf
                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="nama@email.com"
                           @focus="$store.login.isTyping = true" @blur="$store.login.isTyping = false"
                           class="w-full h-12 px-4 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent @error('email') border-red-400 @enderror">
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <input :type="$store.login.showPassword ? 'text' : 'password'" id="password" name="password" required
                               placeholder="••••••••"
                               x-model="$store.login.password"
                               @focus="$store.login.isTyping = true" @blur="$store.login.isTyping = false"
                               class="w-full h-12 px-4 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                        <button type="button" @click="$store.login.showPassword = !$store.login.showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!$store.login.showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="$store.login.showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.592m3.213-2.05A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.965 9.965 0 01-1.563 3.029m-5.858.908a3 3 0 11-4.243-4.243M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-violet-600 rounded">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Ingat saya selama 30 hari</label>
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit"
                        class="w-full h-12 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg transition-colors text-sm">
                    Masuk
                </button>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-gray-400">atau</span></div>
            </div>

            <a href="{{ route('login.google') }}"
               class="w-full inline-flex items-center justify-center gap-2 h-12 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors text-sm">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.6-.2-2.36H12v4.47h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.58-5.17 3.58-8.74z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.9l-3.87-3a7.4 7.4 0 0 1-11.02-3.9H1.06v3.1A12 12 0 0 0 12 24z"/>
                    <path fill="#FBBC05" d="M5.05 14.2a7.2 7.2 0 0 1 0-4.4v-3.1H1.06a12 12 0 0 0 0 10.6z"/>
                    <path fill="#EA4335" d="M12 4.75c1.76 0 3.34.6 4.58 1.79l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.06 6.7l3.99 3.1A7.15 7.15 0 0 1 12 4.75z"/>
                </svg>
                Masuk dengan Google
            </a>

            <div class="text-center text-sm text-gray-500 mt-8">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-gray-900 font-medium hover:underline">Daftar</a>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    // Global reactive state shared by every character/eye + the form fields.
    Alpine.store('login', {
        mouseX: window.innerWidth / 2,
        mouseY: window.innerHeight / 3,
        password: '',
        showPassword: false,
        isTyping: false,
        lookingAtEachOther: false,
        purpleBlink: false,
        blackBlink: false,
        purplePeeking: false,
    });

    window.addEventListener('mousemove', (e) => {
        Alpine.store('login').mouseX = e.clientX;
        Alpine.store('login').mouseY = e.clientY;
    });

    // Independent random blink loops for the purple and black characters.
    const scheduleBlink = (key) => {
        setTimeout(() => {
            Alpine.store('login')[key] = true;
            setTimeout(() => {
                Alpine.store('login')[key] = false;
                scheduleBlink(key);
            }, 150);
        }, Math.random() * 4000 + 3000);
    };
    scheduleBlink('purpleBlink');
    scheduleBlink('blackBlink');

    // When focus moves into an input, characters "look at each other" briefly.
    let lookTimeout = null;
    Alpine.effect(() => {
        const typing = Alpine.store('login').isTyping;
        clearTimeout(lookTimeout);
        if (typing) {
            Alpine.store('login').lookingAtEachOther = true;
            lookTimeout = setTimeout(() => {
                Alpine.store('login').lookingAtEachOther = false;
            }, 800);
        } else {
            Alpine.store('login').lookingAtEachOther = false;
        }
    });

    // While the password is visible, the purple character sneaks a peek every so often.
    let peekTimeout = null;
    Alpine.effect(() => {
        const s = Alpine.store('login');
        const active = s.password.length > 0 && s.showPassword;
        clearTimeout(peekTimeout);
        if (active) {
            const schedule = () => {
                peekTimeout = setTimeout(() => {
                    Alpine.store('login').purplePeeking = true;
                    setTimeout(() => { Alpine.store('login').purplePeeking = false; }, 800);
                    schedule();
                }, Math.random() * 3000 + 2000);
            };
            schedule();
        } else {
            Alpine.store('login').purplePeeking = false;
        }
    });

    // Per-character body lean, based on mouse position relative to the character.
    Alpine.data('characterSkew', () => ({
        skew: 0,
        track() {
            void Alpine.store('login').mouseX;
            void Alpine.store('login').mouseY;
            const r = this.$el.getBoundingClientRect();
            const cx = r.left + r.width / 2;
            const cy = r.top + r.height / 3;
            const dx = Alpine.store('login').mouseX - cx;
            this.skew = Math.max(-6, Math.min(6, -dx / 120));
        },
    }));

    // Eye/pupil tracking: follows the mouse by default, or a forced look
    // direction per character state (typing, peeking, password visible).
    Alpine.data('eyeTrack', (character, maxDist) => ({
        tx: 0,
        ty: 0,
        track() {
            const s = Alpine.store('login');
            const pwShown = s.password.length > 0 && s.showPassword;
            let forced = null;

            if (character === 'purple') {
                if (pwShown) forced = { x: s.purplePeeking ? 4 : -4, y: s.purplePeeking ? 5 : -4 };
                else if (s.lookingAtEachOther) forced = { x: 3, y: 4 };
            } else if (character === 'black') {
                if (pwShown) forced = { x: -4, y: -4 };
                else if (s.lookingAtEachOther) forced = { x: 0, y: -4 };
            } else {
                if (pwShown) forced = { x: -5, y: -4 };
            }

            if (forced) {
                this.tx = forced.x;
                this.ty = forced.y;
                return;
            }

            const r = this.$el.getBoundingClientRect();
            const cx = r.left + r.width / 2;
            const cy = r.top + r.height / 2;
            const dx = s.mouseX - cx;
            const dy = s.mouseY - cy;
            const dist = Math.min(Math.hypot(dx, dy), maxDist);
            const angle = Math.atan2(dy, dx);
            this.tx = Math.cos(angle) * dist;
            this.ty = Math.sin(angle) * dist;
        },
    }));
});
</script>
</body>
</html>
