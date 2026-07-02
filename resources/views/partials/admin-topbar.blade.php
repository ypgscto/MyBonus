@php
    use App\Enums\UserRole;

    $breadcrumbs = $breadcrumbs ?? [];
    $canCreateRequest = in_array(auth()->user()->role, [UserRole::SuperAdmin, UserRole::AdminPmb], true);

    $roleColors = [
        'super_admin' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'admin_pmb' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
        'verifikator' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'keuangan' => 'bg-cyan-100 text-cyan-800 ring-cyan-200',
        'presenter' => 'bg-amber-100 text-amber-800 ring-amber-200',
    ];
    $roleBadgeClass = $roleColors[auth()->user()->role->value] ?? 'bg-slate-100 text-slate-700';
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 lg:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0">
                @if (! empty($breadcrumbs))
                    <nav class="mb-0.5 flex items-center gap-1.5 text-xs text-bonusku-slate" aria-label="Breadcrumb">
                        @foreach ($breadcrumbs as $index => $crumb)
                            @if ($index > 0)
                                <svg class="h-3 w-3 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @endif
                            @if (! empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}" class="truncate hover:text-indigo-600">{{ $crumb['label'] }}</a>
                            @else
                                <span class="truncate font-medium text-slate-500">{{ $crumb['label'] }}</span>
                            @endif
                        @endforeach
                    </nav>
                @endif
                <h1 class="truncate text-lg font-bold text-bonusku-navy">{{ $title ?? 'Dashboard' }}</h1>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            @if ($canCreateRequest)
                <a href="{{ route('presenter-requests.create') }}" class="hidden sm:inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-3.5 py-2 text-sm font-semibold text-white shadow-md shadow-amber-500/25 transition hover:from-amber-600 hover:to-orange-600">
                    <x-icon name="clipboard-plus" class="h-4 w-4" />
                    Buat Permintaan
                </a>
            @endif

            <button type="button" class="relative rounded-xl p-2 text-slate-500 transition hover:bg-slate-100" title="Notifikasi">
                <x-icon name="bell" class="h-5 w-5" />
                <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white"></span>
            </button>

            <div class="relative" @click.outside="profileOpen = false">
                <button type="button" @click="profileOpen = !profileOpen" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white py-1.5 pl-1.5 pr-3 shadow-sm transition hover:bg-slate-50">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-sm font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="hidden text-left sm:block">
                        <div class="max-w-[8rem] truncate text-sm font-semibold text-bonusku-navy">{{ auth()->user()->name }}</div>
                        <span class="inline-flex rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 {{ $roleBadgeClass }}">{{ auth()->user()->role->label() }}</span>
                    </div>
                    <svg class="hidden h-4 w-4 text-slate-400 sm:block" :class="profileOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="profileOpen" x-transition x-cloak class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-bonusku-navy">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-bonusku-slate">{{ auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                        <x-icon name="presenter" class="h-4 w-4 text-slate-400" /> Profil Saya
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
