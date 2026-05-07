<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ProjectHub Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 flex items-center justify-center p-4">

<div class="w-full max-w-md">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-white rounded-2xl shadow-lg mb-4">
            <span class="text-blue-700 font-bold text-xl">PH</span>
        </div>
        <h1 class="text-2xl font-bold text-white">ProjectHub Pro</h1>
        <p class="text-blue-200 text-sm mt-1">Integrated Project & Marketing Managements</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Masuk ke Akun Anda</h2>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-400 @enderror">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-blue-600 rounded">
                <label for="remember" class="ml-2 text-sm text-gray-600">Ingat saya</label>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition-colors text-sm">
                Masuk
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100">
            <p class="text-xs text-gray-400 text-center mb-3">Demo accounts (password: <code class="font-mono bg-gray-100 px-1 rounded">password</code>)</p>
            <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
                <div class="bg-gray-50 rounded px-2 py-1.5"><span class="font-medium text-blue-600">Admin:</span><br>admin@projecthub.pro</div>
                <div class="bg-gray-50 rounded px-2 py-1.5"><span class="font-medium text-green-600">Manager:</span><br>manager@projecthub.pro</div>
                <div class="bg-gray-50 rounded px-2 py-1.5"><span class="font-medium text-purple-600">Dev:</span><br>dev@projecthub.pro</div>
                <div class="bg-gray-50 rounded px-2 py-1.5"><span class="font-medium text-orange-600">Client:</span><br>client@projecthub.pro</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
