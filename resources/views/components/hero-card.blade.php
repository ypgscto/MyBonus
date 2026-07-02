@props([
    'title' => 'Selamat Datang di BONUSKU',
    'subtitle' => 'Kelola permintaan presenter, verifikasi, dan pencairan komisi secara transparan.',
])

<div class="bonusku-gradient-hero relative mb-8 overflow-hidden rounded-2xl p-6 sm:p-8 shadow-card">
    <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-amber-400/20 blur-2xl"></div>
    <div class="absolute bottom-0 right-1/4 h-32 w-32 rounded-full bg-indigo-400/20 blur-2xl"></div>

    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
            <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-amber-400/20 px-3 py-1 text-xs font-semibold text-amber-200 ring-1 ring-amber-300/30">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                Premium Reward Dashboard
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $title }}</h1>
            <p class="mt-2 text-sm text-indigo-100 sm:text-base">{{ $subtitle }}</p>
            @if (isset($actions))
                <div class="mt-5 flex flex-wrap gap-3">
                    {{ $actions }}
                </div>
            @endif
        </div>

        <div class="flex shrink-0 items-end justify-center lg:block">
            <x-mascot size="lg" class="relative z-10" />
        </div>
    </div>
</div>
