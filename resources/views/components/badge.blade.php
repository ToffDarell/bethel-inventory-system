@props([
    'status' => 'In Use',
])

@php
    [$containerClasses, $dotClasses, $icon] = match ($status) {
        'In Use' => [
            'bg-emerald-50 text-emerald-800 border-emerald-200',
            'bg-emerald-600',
            'check'
        ],
        'Under Repair' => [
            'bg-amber-50 text-amber-800 border-amber-200',
            'bg-amber-600',
            'wrench'
        ],
        'Stored' => [
            'bg-slate-100 text-slate-700 border-slate-300',
            'bg-slate-500',
            'archive'
        ],
        'Disposed' => [
            'bg-neutral-100 text-neutral-600 border-neutral-300',
            'bg-neutral-500',
            'x'
        ],
        default => [
            'bg-slate-50 text-slate-700 border-slate-200',
            'bg-slate-400',
            'dot'
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border whitespace-nowrap {$containerClasses}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dotClasses }}" aria-hidden="true"></span>
    <span>{{ $status }}</span>
</span>
