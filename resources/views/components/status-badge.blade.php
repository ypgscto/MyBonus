@props(['status'])

@php
    $class = $status->value === 'aktif'
        ? 'bg-green-100 text-green-800 ring-green-200'
        : 'bg-slate-100 text-slate-700 ring-slate-200';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' . $class]) }}>
    {{ $status->label() }}
</span>
