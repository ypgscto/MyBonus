@props([
    'icon' => 'document',
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-14 text-center shadow-card']) }}>
    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 text-indigo-600 ring-1 ring-indigo-100">
        <x-icon :name="$icon" class="h-8 w-8" />
    </div>
    <h3 class="text-lg font-semibold text-bonusku-navy">{{ $title }}</h3>
    @if ($description)
        <p class="mt-2 max-w-md text-sm text-bonusku-slate">{{ $description }}</p>
    @endif
    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/25 transition hover:from-indigo-700 hover:to-violet-700">
            <x-icon name="clipboard-plus" class="h-4 w-4" />
            {{ $actionLabel }}
        </a>
    @endif
    {{ $slot }}
</div>
