@props([
    'text' => '',
    'targetId' => null,
    'label' => 'Salin',
])

<button
    type="button"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-600 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700']) }}
    title="Salin nomor rekening"
    x-data="{ copied: false }"
    @click="
        const value = {{ $targetId ? "document.getElementById('{$targetId}')?.textContent?.trim()" : json_encode($text) }};
        if (!value || value === '-') return;
        navigator.clipboard.writeText(value).then(() => {
            copied = true;
            setTimeout(() => copied = false, 2000);
        });
    "
>
    <span x-show="!copied" class="inline-flex items-center gap-1">
        <x-icon name="copy" class="h-3.5 w-3.5" />
        <span>{{ $label }}</span>
    </span>
    <span x-show="copied" x-cloak class="inline-flex items-center gap-1 text-emerald-600">
        <x-icon name="check" class="h-3.5 w-3.5" />
        <span>Tersalin</span>
    </span>
</button>
