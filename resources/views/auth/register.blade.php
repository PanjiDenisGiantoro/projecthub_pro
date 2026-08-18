<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Flovig</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            <div class="mb-5 bg-violet-50 border border-violet-200 text-violet-700 rounded-lg px-4 py-3 text-sm">
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
                                :class="plan === '{{ $tier->slug }}' ? 'border-violet-500 bg-violet-50' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            @if($tier->is_popular)
                                <span class="absolute -top-2 left-4 bg-violet-600 text-white text-[10px] font-medium px-2 py-0.5 rounded-full">Paling Populer</span>
                            @endif
                            <div class="flex items-center justify-between {{ $tier->is_popular ? 'mt-1' : '' }}">
                                <p class="text-sm font-semibold text-gray-900">{{ $tier->name }}</p>
                                <svg x-show="plan === '{{ $tier->slug }}'" class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
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
                            <a href="mailto:sales@projecthubpro.id" class="mt-3 text-xs text-center text-violet-600 hover:underline font-medium">{{ $tier->cta_label }}</a>
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
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent @error('name') border-red-400 @enderror">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Kerja</label>
                <input type="email" id="email" name="email" value="{{ old('email', $prefillEmail) }}" required
                       placeholder="budi@perusahaan.com"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent @error('email') border-red-400 @enderror">
            </div>

            <div>
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan</label>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required
                       placeholder="PT Maju Bersama"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent @error('company_name') border-red-400 @enderror">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Minimal 8 karakter"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent @error('password') border-red-400 @enderror">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       placeholder="Ulangi password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent">
            </div>

            {{-- Package Selection (selalu aktif, tidak bisa diubah) --}}
            <div class="pt-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Paket Aplikasi
                </label>
                <p class="text-xs text-gray-500 mb-3">Semua akun otomatis mendapat modul berikut.</p>

                <div class="grid grid-cols-1 gap-3">

                    {{-- Task Management Card --}}
                    <div class="relative flex flex-col rounded-xl border-2 border-violet-500 bg-blue-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center bg-blue-500">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-blue-700">
                                    Task Management
                                </p>
                                <p class="text-xs mt-0.5 text-blue-600">
                                    Proyek, tugas, bug ticket, milestone
                                </p>
                            </div>
                            <div class="flex-shrink-0 w-5 h-5 rounded-full border-2 border-violet-500 bg-blue-500 flex items-center justify-center mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <ul class="mt-3 space-y-1 text-blue-600">
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
                    </div>

                    {{-- HRIS Card — disembunyikan sementara --}}

                </div>
            </div>

            <button type="submit"
                    class="w-full font-medium py-2.5 rounded-lg transition-colors text-sm mt-2 bg-violet-600 hover:bg-violet-700 text-white cursor-pointer">
                Buat Akun
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-violet-600 hover:underline font-medium">Masuk</a>
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
        plan: @json(old('plan', $prefillPlan)),
    }
}
</script>

</body>
</html>
