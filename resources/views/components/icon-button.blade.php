@props([
    'variant' => 'outline', // 'outline', 'ghost', 'primary', 'danger'
    'size' => 'md', // 'sm' (32px), 'md' (36px), 'lg' (44px)
    'href' => null,
    'type' => 'button',
    'title' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center shrink-0 rounded-lg transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 cursor-pointer disabled:opacity-50 disabled:pointer-events-none select-none relative group';

    $sizeClasses = match ($size) {
        'sm' => 'w-8 h-8 text-xs',
        'lg' => 'w-11 h-11 text-base',
        default => 'w-9 h-9 text-sm',
    };

    $variantClasses = match ($variant) {
        'ghost' => 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus-visible:ring-primary',
        'primary' => 'bg-primary text-white hover:bg-primary-hover shadow-2xs focus-visible:ring-primary',
        'danger' => 'text-slate-600 hover:text-brand-red hover:bg-red-50 hover:border-red-200 border border-transparent focus-visible:ring-brand-red',
        default => 'border border-slate-200 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50 hover:border-slate-300 shadow-2xs focus-visible:ring-primary',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" title="{{ $title }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
        @if ($title)
            <span class="sr-only">{{ $title }}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" title="{{ $title }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
        @if ($title)
            <span class="sr-only">{{ $title }}</span>
        @endif
    </button>
@endif
