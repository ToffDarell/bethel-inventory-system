@props([
    'condition' => null,
])

@php
    $label = $condition ?: 'Not set';

    [$containerClasses, $iconClasses, $iconPath] = match ($condition) {
        'Brand-new' => [
            'bg-blue-50 text-blue-700 border-blue-200',
            'text-blue-500',
            'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
        ],
        'In Good Condition' => [
            'bg-emerald-50 text-emerald-700 border-emerald-200',
            'text-emerald-500',
            'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'Refurbish' => [
            'bg-amber-50 text-amber-700 border-amber-200',
            'text-amber-500',
            'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        ],
        'Damage' => [
            'bg-rose-50 text-rose-700 border-rose-200',
            'text-rose-500',
            'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        default => [
            'bg-slate-100 text-slate-600 border-slate-200',
            'text-slate-400',
            'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border whitespace-nowrap {$containerClasses}"]) }}>
    <svg class="w-3.5 h-3.5 shrink-0 {{ $iconClasses }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
    </svg>
    <span>{{ $label }}</span>
</span>
