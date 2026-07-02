@props(['action' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card']) }}>
    @if (isset($header))
        <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
            <h3 class="text-base font-semibold text-bonusku-navy">{{ $header }}</h3>
        </div>
    @endif
    <div class="p-5 sm:p-6">
        {{ $slot }}
    </div>
</div>
