<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat &amp; Ketentuan — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-gray-50">

<div class="max-w-3xl mx-auto px-4 py-12">
    <a href="{{ route('home') }}" class="inline-flex items-center mb-8">
        <img src="{{ asset('flovig_logo.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto object-contain">
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 md:p-10">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Syarat &amp; Ketentuan</h1>
        <p class="text-sm text-gray-500 mb-8">Terakhir diperbarui: {{ now()->translatedFormat('d F Y') }}</p>

        <div class="prose prose-sm md:prose-base max-w-none prose-headings:font-bold prose-headings:text-gray-900 prose-a:text-blue-600 text-gray-700 space-y-6">

            <p>
                Selamat datang di {{ config('app.name') }}. Dengan membuat akun atau menggunakan layanan kami, Anda
                setuju untuk terikat pada Syarat &amp; Ketentuan berikut. Jika Anda tidak setuju, mohon tidak
                menggunakan layanan ini.
            </p>

            <h2>1. Layanan</h2>
            <p>
                {{ config('app.name') }} adalah platform manajemen proyek, tugas, sprint, dan tim yang menyediakan
                fitur kolaborasi, pelaporan, serta integrasi opsional dengan layanan pihak ketiga seperti Google
                Calendar.
            </p>

            <h2>2. Akun Pengguna</h2>
            <ul>
                <li>Anda bertanggung jawab menjaga kerahasiaan kredensial akun Anda.</li>
                <li>Anda bertanggung jawab atas seluruh aktivitas yang terjadi melalui akun Anda.</li>
                <li>Anda wajib memberikan informasi yang akurat saat mendaftar.</li>
            </ul>

            <h2>3. Integrasi Google</h2>
            <p>
                Jika Anda menghubungkan akun Google untuk login atau sinkronisasi Google Calendar, Anda memberikan
                izin kepada {{ config('app.name') }} untuk mengakses data terkait sesuai scope yang diminta pada
                layar persetujuan Google. Anda dapat mencabut izin ini kapan saja — lihat
                <a href="{{ route('legal.privacy') }}">Kebijakan Privasi</a> untuk detail lebih lanjut.
            </p>

            <h2>4. Penggunaan yang Dilarang</h2>
            <p>Anda setuju untuk tidak:</p>
            <ul>
                <li>Menyalahgunakan layanan untuk tujuan ilegal atau merugikan pihak lain.</li>
                <li>Mencoba mengakses data pengguna lain tanpa izin.</li>
                <li>Mengganggu atau merusak infrastruktur layanan.</li>
            </ul>

            <h2>5. Batasan Tanggung Jawab</h2>
            <p>
                Layanan disediakan "sebagaimana adanya" (as-is). Kami berupaya menjaga ketersediaan dan keakuratan
                layanan, namun tidak menjamin layanan bebas dari gangguan atau kesalahan sepenuhnya.
            </p>

            <h2>6. Penghentian Akun</h2>
            <p>
                Kami berhak menangguhkan atau menghentikan akun yang melanggar Syarat &amp; Ketentuan ini. Anda juga
                dapat meminta penghapusan akun kapan saja dengan menghubungi kami.
            </p>

            <h2>7. Perubahan Ketentuan</h2>
            <p>
                Kami dapat memperbarui Syarat &amp; Ketentuan ini dari waktu ke waktu. Perubahan material akan
                diinformasikan melalui email atau notifikasi di dalam aplikasi.
            </p>

            <h2>8. Kontak</h2>
            <p>
                Pertanyaan seputar Syarat &amp; Ketentuan dapat dikirimkan ke
                <a href="mailto:panjidenisgiantoroo@gmail.com">panjidenisgiantoroo@gmail.com</a>.
            </p>
        </div>
    </div>

    <p class="text-center text-sm text-gray-400 mt-8">
        &copy; {{ now()->year }} {{ config('app.name') }}. Lihat juga
        <a href="{{ route('legal.privacy') }}" class="underline hover:text-gray-600">Kebijakan Privasi</a>.
    </p>
</div>

</body>
</html>
