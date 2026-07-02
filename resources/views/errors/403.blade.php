<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-100 text-red-600">
            <x-icon name="x-circle" class="h-10 w-10" />
        </div>
        <h1 class="mt-6 text-5xl font-bold text-slate-900">403</h1>
        <h2 class="mt-3 text-xl font-semibold text-slate-900">Akses Ditolak</h2>
        <p class="mt-3 text-sm text-slate-500">
            Anda tidak memiliki izin untuk mengakses halaman ini.
            Role Anda tidak sesuai dengan akses yang diperlukan.
        </p>
        <div class="mt-8">
            @auth
                <a href="{{ route(auth()->user()->role->dashboardRoute()) }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Kembali ke Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Login
                </a>
            @endauth
        </div>
    </div>
</body>
</html>
