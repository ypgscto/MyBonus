@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    @if ($title)
        <h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>
    @endif
    @if ($description)
        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
    @endif
</div>
