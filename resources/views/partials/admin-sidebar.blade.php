@php
    $menuItems = config('menu.' . auth()->user()->role->value, []);
    $currentRoute = request()->route()?->getName() ?? '';

    $isRouteActive = function (array $item) use ($currentRoute): bool {
        if (! empty($item['routes'])) {
            return in_array($currentRoute, $item['routes'], true);
        }

        $routePrefix = $item['route_prefix'] ?? null;

        return $routePrefix && (
            str_starts_with($currentRoute, $routePrefix)
            || $currentRoute === $routePrefix
        );
    };

    $roleBadgeColors = [
        'super_admin' => 'bg-amber-400/20 text-amber-200 ring-amber-400/40',
        'admin_pmb' => 'bg-indigo-400/20 text-indigo-200 ring-indigo-400/40',
        'verifikator' => 'bg-emerald-400/20 text-emerald-200 ring-emerald-400/40',
        'keuangan' => 'bg-cyan-400/20 text-cyan-200 ring-cyan-400/40',
        'presenter' => 'bg-amber-400/20 text-amber-200 ring-amber-400/40',
    ];
    $roleBadge = $roleBadgeColors[auth()->user()->role->value] ?? 'bg-white/10 text-white/80 ring-white/20';
@endphp

{{-- Mobile overlay --}}
<div
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-bonusku-navy/70 backdrop-blur-sm lg:hidden"
    @click="sidebarOpen = false"
    x-cloak
></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-bonusku-navy text-white shadow-2xl shadow-black/30 transition-transform duration-300 ease-in-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    aria-label="Sidebar navigasi"
>
    {{-- Accent strip --}}
    <div class="pointer-events-none absolute inset-y-0 right-0 w-px bg-gradient-to-b from-transparent via-amber-400/30 to-transparent"></div>

    {{-- Header --}}
    <div class="bonusku-sidebar-header px-5 py-5">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-400/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -left-4 bottom-0 h-16 w-16 rounded-full bg-indigo-500/15 blur-xl"></div>

        <div class="relative flex items-start justify-between gap-3">
            <div class="flex min-w-0 flex-1 items-start gap-3">
                <x-app-logo class="!h-11 !w-11 !rounded-xl !shadow-lg !shadow-amber-500/20" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-extrabold tracking-wide text-white">BONUSKU</span>
                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
                    </div>
                    <p class="mt-0.5 text-xs leading-snug text-slate-300">
                        Aplikasi Presenter Mahasiswa PMB
                    </p>
                    <span class="mt-2 inline-flex items-center gap-1 rounded-lg bg-amber-400/15 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-amber-200 ring-1 ring-amber-400/30">
                        <svg class="h-3 w-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        STIKES Gunung Sari
                    </span>
                </div>
            </div>

            <button
                type="button"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                @click="sidebarOpen = false"
                aria-label="Tutup menu"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500">Menu Utama</p>

        @foreach ($menuItems as $item)
            @if (($item['type'] ?? null) === 'group')
                @php
                    $groupActive = $isRouteActive($item);
                    foreach ($item['children'] ?? [] as $child) {
                        if ($isRouteActive($child)) {
                            $groupActive = true;
                            break;
                        }
                    }
                @endphp

                <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }" class="space-y-1">
                    <button
                        type="button"
                        @click="open = !open"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-200 {{ $groupActive ? 'bonusku-sidebar-group-active' : 'bonusku-sidebar-hover' }}"
                    >
                        <span class="bonusku-sidebar-icon {{ $groupActive ? '!bg-indigo-500/30 !text-amber-200 !ring-amber-400/30' : '' }}">
                            <x-icon :name="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span class="flex-1 text-left leading-none">{{ $item['label'] }}</span>
                        <svg
                            class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                            :class="open ? 'rotate-180 text-amber-300' : ''"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="ml-3 space-y-1 border-l border-white/10 pl-2"
                    >
                        @foreach ($item['children'] as $child)
                            @php $childActive = $isRouteActive($child); @endphp
                            <a
                                href="{{ route($child['route']) }}"
                                @click="sidebarOpen = false"
                                class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition duration-200 {{ $childActive ? 'bonusku-sidebar-child-active' : 'text-slate-300 bonusku-sidebar-hover' }}"
                            >
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $childActive ? 'bg-white/15 text-amber-200' : 'bg-white/5 text-slate-400' }}">
                                    <x-icon :name="$child['icon']" class="h-4 w-4" />
                                </span>
                                <span class="leading-none">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                @php
                    $isActive = $isRouteActive($item);
                    $isDisabled = empty($item['route']);
                @endphp

                @if ($isDisabled)
                    <span
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-500 cursor-not-allowed"
                        title="Segera hadir"
                    >
                        <span class="bonusku-sidebar-icon opacity-40">
                            <x-icon :name="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span class="leading-none">{{ $item['label'] }}</span>
                        <span class="ml-auto rounded-md bg-white/5 px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-white/10">Soon</span>
                    </span>
                @else
                    <a
                        href="{{ route($item['route']) }}"
                        @click="sidebarOpen = false"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-200 {{ $isActive ? 'bonusku-sidebar-active' : 'bonusku-sidebar-hover' }}"
                    >
                        <span class="bonusku-sidebar-icon {{ $isActive ? '' : 'group-hover:bg-white/10' }}">
                            <x-icon :name="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span class="leading-none">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endif
        @endforeach
    </nav>

    {{-- User card --}}
    <div class="border-t border-white/10 bg-bonusku-navy-soft/50 p-4">
        <div class="rounded-xl bg-gradient-to-br from-white/10 to-white/5 p-3 ring-1 ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-violet-600 to-purple-700 text-sm font-bold text-white shadow-lg shadow-indigo-900/40 ring-2 ring-amber-400/20">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                    <span class="mt-1 inline-flex rounded-md px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $roleBadge }}">
                        {{ auth()->user()->role->label() }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</aside>
