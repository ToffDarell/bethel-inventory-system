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
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs focus-visible:ring-[#0f5d8c]',
        'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs focus-visible:ring-[#0f5d8c]',
        'danger' => 'bg-[#b91c1c] text-white hover:bg-[#991b1b] shadow-2xs focus-visible:ring-[#b91c1c]',
        'danger-outline' => 'border border-slate-300 bg-white text-[#b91c1c] hover:bg-red-50/70 hover:border-red-300 focus-visible:ring-[#b91c1c]',
        'ghost' => 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus-visible:ring-[#0f5d8c]',
        default => 'bg-[#0f5d8c] text-white hover:bg-[#0c4a70] shadow-2xs focus-visible:ring-[#0f5d8c]',
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
