<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi — {{ config('app.name') }}</title>
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
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Kebijakan Privasi</h1>
        <p class="text-sm text-gray-500 mb-8">Terakhir diperbarui: {{ now()->translatedFormat('d F Y') }}</p>

        <div class="prose prose-sm md:prose-base max-w-none prose-headings:font-bold prose-headings:text-gray-900 prose-a:text-blue-600 text-gray-700 space-y-6">

            <p>
                {{ config('app.name') }} ("kami") adalah platform manajemen proyek, tugas, dan tim yang dioperasikan oleh
                developer independen dengan kontak {{ config('mail.legal_contact', 'panjidenisgiantoroo@gmail.com') }}.
                Kebijakan Privasi ini menjelaskan data apa yang kami kumpulkan dari pengguna ("Anda"), bagaimana data
                tersebut digunakan, disimpan, dan dilindungi, termasuk saat Anda menghubungkan akun Google Anda.
            </p>

            <h2>1. Data yang Kami Kumpulkan</h2>
            <p>Kami mengumpulkan data berikut sesuai penggunaan Anda atas layanan kami:</p>
            <ul>
                <li><strong>Data akun:</strong> nama, alamat email, dan kata sandi (terenkripsi) saat Anda mendaftar.</li>
                <li><strong>Data proyek &amp; pekerjaan:</strong> proyek, tugas (task), sprint, komentar, lampiran, dan aktivitas lain yang Anda buat di dalam aplikasi.</li>
                <li>
                    <strong>Data dari Google (opsional):</strong> jika Anda memilih untuk masuk (login) menggunakan Google
                    atau menghubungkan Google Calendar, kami menerima nama, alamat email, foto profil dari akun Google Anda,
                    serta — khusus untuk fitur sinkronisasi kalender — akses terbatas ke event Google Calendar Anda
                    melalui scope <code>https://www.googleapis.com/auth/calendar.events</code>.
                </li>
            </ul>

            <h2>2. Bagaimana Kami Menggunakan Data Google Anda</h2>
            <p>Akses ke Google Calendar API digunakan semata-mata untuk:</p>
            <ul>
                <li>Membuat, membaca, memperbarui, dan menghapus event kalender yang berasal dari jadwal meeting, sprint, atau tenggat tugas yang Anda buat di {{ config('app.name') }}, agar tersinkron otomatis ke Google Calendar pribadi Anda.</li>
                <li>Menampilkan status koneksi kalender Anda di halaman profil.</li>
            </ul>
            <p>
                Kami <strong>tidak</strong> menggunakan data Google Anda untuk iklan, tidak menjualnya, dan tidak
                membagikannya ke pihak ketiga mana pun di luar penyedia infrastruktur yang menjalankan aplikasi ini
                (mis. hosting server). Penggunaan dan transfer data yang diterima dari Google API mematuhi
                <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener">Google API Services User Data Policy</a>,
                termasuk persyaratan Limited Use.
            </p>

            <h2>3. Penyimpanan &amp; Keamanan Data</h2>
            <p>
                Token akses Google (access token &amp; refresh token) disimpan terenkripsi di database kami dan hanya
                digunakan oleh server untuk melakukan panggilan ke Google Calendar API atas nama Anda. Kami menerapkan
                praktik keamanan standar (enkripsi in-transit via HTTPS, kontrol akses berbasis peran) untuk melindungi
                seluruh data pengguna.
            </p>

            <h2>4. Mencabut Akses</h2>
            <p>
                Anda dapat memutus koneksi Google Calendar kapan saja melalui halaman Profil → tombol
                "Putuskan Google Calendar", atau langsung mencabut izin aplikasi melalui halaman
                <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">Google Account Permissions</a>.
                Saat koneksi diputus, token yang tersimpan di sistem kami akan dihapus.
            </p>

            <h2>5. Penghapusan Data</h2>
            <p>
                Anda dapat meminta penghapusan akun beserta seluruh data terkait dengan menghubungi kami melalui email
                di bawah. Permintaan akan diproses paling lambat 30 hari kerja.
            </p>

            <h2>6. Perubahan Kebijakan</h2>
            <p>
                Kami dapat memperbarui Kebijakan Privasi ini sewaktu-waktu. Perubahan material akan diinformasikan
                melalui email atau notifikasi di dalam aplikasi.
            </p>

            <h2>7. Kontak</h2>
            <p>
                Pertanyaan seputar privasi dapat dikirimkan ke
                <a href="mailto:panjidenisgiantoroo@gmail.com">panjidenisgiantoroo@gmail.com</a>.
            </p>
        </div>
    </div>

    <p class="text-center text-sm text-gray-400 mt-8">
        &copy; {{ now()->year }} {{ config('app.name') }}. Lihat juga
        <a href="{{ route('legal.terms') }}" class="underline hover:text-gray-600">Syarat &amp; Ketentuan</a>.
    </p>
</div>

</body>
</html>
