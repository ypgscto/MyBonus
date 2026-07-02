@props([
    'label',
    'value',
    'icon' => 'dashboard',
    'color' => 'indigo',
    'href' => null,
    'money' => false,
])

@php
    $palettes = [
        'indigo' => ['bg' => 'from-indigo-500 to-violet-600', 'light' => 'bg-indigo-50 text-indigo-600', 'ring' => 'hover:ring-indigo-200'],
        'blue' => ['bg' => 'from-blue-500 to-indigo-600', 'light' => 'bg-blue-50 text-blue-600', 'ring' => 'hover:ring-blue-200'],
        'gold' => ['bg' => 'from-amber-400 to-orange-500', 'light' => 'bg-amber-50 text-amber-600', 'ring' => 'hover:ring-amber-200'],
        'emerald' => ['bg' => 'from-emerald-500 to-teal-600', 'light' => 'bg-emerald-50 text-emerald-600', 'ring' => 'hover:ring-emerald-200'],
        'purple' => ['bg' => 'from-purple-500 to-violet-600', 'light' => 'bg-purple-50 text-purple-600', 'ring' => 'hover:ring-purple-200'],
        'red' => ['bg' => 'from-red-500 to-rose-600', 'light' => 'bg-red-50 text-red-600', 'ring' => 'hover:ring-red-200'],
        'slate' => ['bg' => 'from-slate-500 to-slate-700', 'light' => 'bg-slate-100 text-slate-600', 'ring' => 'hover:ring-slate-200'],
        'cyan' => ['bg' => 'from-cyan-500 to-blue-600', 'light' => 'bg-cyan-50 text-cyan-600', 'ring' => 'hover:ring-cyan-200'],
    ];
    $palette = $palettes[$color] ?? $palettes['indigo'];
    $display = $money ? 'Rp ' . number_format((float) $value, 0, ',', '.') : number_format((float) $value, 0, ',', '.');
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    class="group block rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card transition duration-200 hover:-translate-y-0.5 hover:shadow-card-hover hover:ring-2 {{ $palette['ring'] }}">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-bonusku-slate truncate">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-bonusku-navy sm:text-3xl">{{ $display }}</p>
        </div>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $palette['bg'] }} text-white shadow-md transition group-hover:scale-105">
            <x-icon :name="$icon" class="h-6 w-6" />
        </div>
    </div>
</{{ $tag }}>
