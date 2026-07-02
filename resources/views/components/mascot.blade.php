@props([
    'size' => 'md',
    'bounce' => true,
    'alt' => 'Maskot BONUSKU',
])

@php
    $sizes = [
        'sm' => 'w-32 sm:w-40',
        'md' => 'w-44 sm:w-52 lg:w-56',
        'lg' => 'w-56 sm:w-64 lg:w-72 xl:w-80',
        'xl' => 'w-64 sm:w-80 lg:w-96',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<img
    src="{{ asset('images/bonusku-mascot.png') }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => trim($sizeClass . ' h-auto object-contain drop-shadow-2xl mix-blend-screen ' . ($bounce ? 'bonusku-mascot-bounce' : ''))]) }}
    loading="lazy"
/>
