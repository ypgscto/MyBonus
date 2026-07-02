@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card']) }}>
    @if ($title || $description)
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            @if ($title)
                <h3 class="text-base font-semibold text-bonusku-navy">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="mt-1 text-sm text-bonusku-slate">{{ $description }}</p>
            @endif
        </div>
    @endif

    @if (isset($filters))
        <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
            {{ $filters }}
        </div>
    @endif

    <div class="overflow-x-auto">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $footer }}
        </div>
    @endif
</div>
