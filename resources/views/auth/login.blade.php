<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - BONUSKU</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100">
    <div class="flex min-h-screen">
        {{-- Panel kiri: branding --}}
        <div class="relative hidden w-5/12 bonusku-gradient-hero lg:flex lg:flex-col lg:items-center lg:justify-center lg:p-10 xl:w-2/5">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -left-10 top-20 h-48 w-48 rounded-full bg-amber-400/15 blur-3xl"></div>
                <div class="absolute bottom-10 right-10 h-40 w-40 rounded-full bg-indigo-400/20 blur-3xl"></div>
            </div>

            <div class="relative z-10 max-w-sm text-center">
                <div class="mb-8 flex items-center justify-center gap-3">
                    <x-app-logo class="!h-12 !w-12" />
                    <div class="text-left">
                        <div class="text-2xl font-extrabold text-white">BONUSKU</div>
                        <div class="text-sm text-indigo-200">STIKES Gunung Sari</div>
                    </div>
                </div>

                <p class="text-lg font-semibold leading-relaxed text-white">
                    Aplikasi Presenter Mahasiswa PMB
                </p>
                <p class="mt-4 text-sm font-medium text-amber-200/90">
                    Presenter Berprestasi, Bonus Mengalir Tiada Henti
                </p>
            </div>
        </div>

        {{-- Panel kanan: maskot di samping box login --}}
        <div class="flex w-full flex-1 items-center justify-center px-4 py-10 sm:px-8 lg:px-10">
            <div class="flex w-full max-w-3xl flex-col items-center gap-6 sm:flex-row sm:items-center sm:justify-center sm:gap-8">
                <div class="shrink-0">
                    <x-mascot size="md" class="mx-auto sm:hidden" />
                    <x-mascot size="lg" class="mx-auto hidden sm:block lg:hidden" />
                    <x-mascot size="xl" class="mx-auto hidden lg:block" />
                </div>

                <div class="w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-6 shadow-card sm:p-8">
                    <div class="mb-6 lg:hidden text-center">
                        <x-app-logo class="mx-auto mb-2 !h-10 !w-10" />
                        <h1 class="text-xl font-bold text-bonusku-navy">BONUSKU</h1>
                        <p class="text-xs text-bonusku-slate">Aplikasi Presenter Mahasiswa PMB</p>
                    </div>

                    <h2 class="text-xl font-bold text-bonusku-navy">Masuk ke akun Anda</h2>
                    <p class="mt-1 text-sm text-bonusku-slate">Gunakan kredensial yang telah diberikan.</p>

                    @if (session('status'))
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <label for="email" class="mb-1 block text-sm font-semibold text-bonusku-navy">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="bonusku-input @error('email') !border-red-500 @enderror" />
                            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password" class="mb-1 block text-sm font-semibold text-bonusku-navy">Password</label>
                            <input id="password" type="password" name="password" required class="bonusku-input @error('password') !border-red-500 @enderror" />
                            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <label class="flex items-center gap-2 text-sm text-bonusku-slate">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            Ingat saya
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700">
                            Masuk ke BONUSKU
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
