@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 cursor-pointer disabled:opacity-50 disabled:pointer-events-none';

    $sizeClasses = match ($size) {
        'sm' => 'min-h-[36px] px-3.5 py-1.5 text-xs rounded-lg',
        'lg' => 'min-h-[48px] px-6 py-3 text-base rounded-xl',
        default => 'min-h-[44px] px-5 py-2.5 text-sm rounded-lg',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs focus-visible:ring-[#0f5d8c]',
        'outline' => 'border border-[#0f5d8c] bg-white text-[#0f5d8c] hover:bg-sky-50 shadow-2xs focus-visible:ring-[#0f5d8c]',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 shadow-xs focus-visible:ring-red-600',
        'danger-outline' => 'border border-red-300 bg-white text-red-700 hover:bg-red-50 focus-visible:ring-red-600',
        'ghost' => 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
        default => 'bg-[#0f5d8c] text-white hover:bg-[#0c4a6e] shadow-xs focus-visible:ring-[#0f5d8c]',
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
