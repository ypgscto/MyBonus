@props(['status', 'mode' => 'request'])

@php
    $label = $mode === 'payout' ? $status->payoutLabel() : $status->presenterLabel();
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' . $status->badgeClass()]) }}>
    {{ $label }}
</span>
