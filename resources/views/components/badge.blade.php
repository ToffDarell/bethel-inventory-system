@props([
    'status' => 'In Use',
])

@php
    [$containerClasses, $dotClasses, $iconPath] = match ($status) {
        'In Use' => [
            'bg-emerald-50 text-emerald-800 border-emerald-200',
            'text-emerald-600',
            'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'Under Repair' => [
            'bg-amber-50 text-amber-800 border-amber-300',
            'text-amber-600',
            'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        'Stored' => [
            'bg-slate-100 text-slate-700 border-slate-300',
            'text-slate-500',
            'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
        ],
        'Disposed' => [
            'bg-neutral-100 text-neutral-600 border-neutral-300',
            'text-neutral-500',
            'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
        ],
        default => [
            'bg-slate-50 text-slate-700 border-slate-200',
            'text-slate-500',
            'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border whitespace-nowrap {$containerClasses}"]) }}>
    <svg class="w-3.5 h-3.5 shrink-0 {{ $dotClasses }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
    </svg>
    <span>{{ $status }}</span>
</span>
