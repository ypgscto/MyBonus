@props(['title' => 'Dashboard', 'breadcrumbs' => []])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - BONUSKU</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-slate-100/80 text-slate-900" x-data="{ sidebarOpen: false, profileOpen: false }">
    @include('partials.admin-sidebar')

    <div class="lg:pl-72 flex min-h-screen flex-col transition-all">
        @include('partials.admin-topbar', ['title' => $title, 'breadcrumbs' => $breadcrumbs])

        <main class="flex-1 p-4 pb-20 sm:p-6 sm:pb-20 lg:p-8 lg:pb-20">
            @include('partials.flash-messages')
            {{ $slot }}
        </main>
    </div>

    <footer class="fixed bottom-0 left-0 right-0 z-20 border-t border-slate-200/80 bg-white/95 py-2.5 text-center text-xs text-bonusku-slate backdrop-blur-md lg:left-72">
        <span class="font-medium text-bonusku-navy">Developed by</span>
        <span class="font-semibold text-indigo-600"> KAT Inovasi Bersama</span>
        <span class="text-slate-400"> — </span>
        <span class="font-medium text-amber-600">IT Division</span>
    </footer>

    <x-confirm-modal />
    @stack('scripts')
</body>
</html>
