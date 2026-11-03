@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 cursor-pointer disabled:opacity-50 disabled:pointer-events-none select-none';

    $sizeClasses = match ($size) {
        'sm' => 'min-h-[36px] px-3 py-1.5 text-xs rounded-lg',
        'lg' => 'min-h-[48px] px-6 py-3 text-base rounded-xl',
        default => 'min-h-[44px] px-4 py-2 text-sm rounded-lg',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs focus-visible:ring-primary',
        'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs focus-visible:ring-primary',
        'danger' => 'border border-brand-red/30 bg-white text-brand-red hover:bg-red-50 hover:border-brand-red shadow-2xs focus-visible:ring-brand-red',
        'danger-solid' => 'bg-brand-red text-white hover:bg-brand-red-hover shadow-2xs focus-visible:ring-brand-red',
        'ghost' => 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus-visible:ring-primary',
        default => 'bg-primary text-white hover:bg-primary-hover shadow-2xs focus-visible:ring-primary',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
