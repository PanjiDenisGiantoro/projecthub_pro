<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Flovig</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4 py-10">

<div class="w-full max-w-4xl" x-data="registerForm()">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <a href="/" class="inline-flex items-center mb-4">
            <img src="{{ asset('flovig_logo.png') }}" alt="Flovig" class="h-9 w-auto object-contain">
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Buat akun baru</h1>
        <p class="text-gray-500 text-sm mt-1">Gratis selamanya, tanpa kartu kredit</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

        @if(session('status'))
            <div class="mb-5 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Pricing Plan Selection --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Paket Harga</label>
            <p class="text-xs text-gray-500 mb-3">Sederhana, transparan, terjangkau. Ganti kapan saja.</p>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($tiers as $tier)
                    @if($tier->cta_type === 'register')
                        {{-- Paket bisa dipilih langsung saat daftar --}}
                        <button type="button" @click="plan = '{{ $tier->slug }}'"
                                class="relative flex flex-col text-left rounded-xl border-2 p-4 transition-all"
                                :class="plan === '{{ $tier->slug }}' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            @if($tier->is_popular)
                                <span class="absolute -top-2 left-4 bg-blue-600 text-white text-[10px] font-medium px-2 py-0.5 rounded-full">Paling Populer</span>
                            @endif
                            <div class="flex items-center justify-between {{ $tier->is_popular ? 'mt-1' : '' }}">
                                <p class="text-sm font-semibold text-gray-900">{{ $tier->name }}</p>
                                <svg x-show="plan === '{{ $tier->slug }}'" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $tier->tagline }}</p>
                            <p class="text-lg font-bold text-gray-900 mt-2">{{ $tier->priceDisplay() }}</p>
                            <p class="text-xs text-gray-500 -mt-0.5">{{ $tier->price_period }}</p>
                            <ul class="mt-3 space-y-1 text-gray-500">
                                @foreach($tier->features as $feature)
                                    <li class="text-xs">✓ {{ $feature->label }}</li>
                                @endforeach
                            </ul>
                        </button>
                    @else
                        {{-- Paket contact-sales (mis. Enterprise) — tidak bisa dipilih langsung --}}
                        <div class="relative flex flex-col rounded-xl border-2 border-gray-200 bg-white p-4">
                            <p class="text-sm font-semibold text-gray-900">{{ $tier->name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $tier->tagline }}</p>
                            <p class="text-lg font-bold text-gray-900 mt-2">{{ $tier->priceDisplay() }}</p>
                            <p class="text-xs text-gray-500 -mt-0.5">{{ $tier->price_period }}</p>
                            <ul class="mt-3 space-y-1 text-gray-500 flex-1">
                                @foreach($tier->features as $feature)
                                    <li class="text-xs">✓ {{ $feature->label }}</li>
                                @endforeach
                            </ul>
                            <a href="mailto:sales@projecthubpro.id" class="mt-3 text-xs text-center text-blue-600 hover:underline font-medium">{{ $tier->cta_label }}</a>
                        </div>
                    @endif
                @endforeach
            </div>

            <p class="text-xs text-gray-500 mt-3" x-show="plan === 'free'">Mulai gratis selamanya, tanpa kartu kredit.</p>
            <p class="text-xs text-gray-500 mt-3" x-show="plan !== 'free'">Setelah mendaftar, Anda akan diarahkan ke halaman pembayaran Midtrans untuk menyelesaikan langganan.</p>
        </div>

        <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="plan" x-model="plan">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name', $prefillName) }}" required autofocus
                       placeholder="Budi Santoso"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-400 @enderror">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Kerja</label>
                <input type="email" id="email" name="email" value="{{ old('email', $prefillEmail) }}" required
                       placeholder="budi@perusahaan.com"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-400 @enderror">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan</label>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required
                       placeholder="PT Maju Bersama"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('company_name') border-red-400 @enderror">
                @error('company_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required minlength="8"
                           placeholder="Minimal 8 karakter"
                           class="w-full px-4 py-2.5 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-400 @enderror">
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
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                <div class="relative">
                    <input :type="showPasswordConfirmation ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required minlength="8"
                           placeholder="Ulangi password"
                           class="w-full px-4 py-2.5 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg x-show="!showPasswordConfirmation" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg x-show="showPasswordConfirmation" x-cloak class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.592m3.213-2.05A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.965 9.965 0 01-1.563 3.029m-5.858.908a3 3 0 11-4.243-4.243M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="pt-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Modul Aplikasi</label>
                <p class="text-xs text-gray-500 mb-2">Pilih modul yang ingin Anda aktifkan. Bisa diubah lagi nanti.</p>

                <div class="space-y-2">
                    <label class="flex items-start gap-2.5 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-gray-300 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="checkbox" name="modules[]" value="task_management" x-model="modules"
                               class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-900">Task Management</span>
                            <span class="block text-xs text-gray-500">Proyek, tugas, bug ticket, dan laporan.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2.5 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-gray-300 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="checkbox" name="modules[]" value="hris" x-model="modules"
                               class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-900">HRIS</span>
                            <span class="block text-xs text-gray-500">Data karyawan, absensi, penggajian, dan cuti.</span>
                        </span>
                    </label>
                </div>
                @error('modules') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full font-medium py-2.5 rounded-lg transition-colors text-sm mt-2 bg-blue-600 hover:bg-blue-700 text-white cursor-pointer">
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
        <a href="{{ route('legal.terms') }}" target="_blank" class="underline hover:text-gray-600">Syarat & Ketentuan</a>
        dan <a href="{{ route('legal.privacy') }}" target="_blank" class="underline hover:text-gray-600">Kebijakan Privasi</a> kami.
    </p>
</div>

<script>
function registerForm() {
    return {
        plan: @json(old('plan', $prefillPlan)),
        modules: @json(old('modules', ['task_management', 'hris'])),
        showPassword: false,
        showPasswordConfirmation: false,
    }
}
</script>

</body>
</html>
