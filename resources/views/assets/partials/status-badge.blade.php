@php
    [$badgeClasses, $dotClasses] = match ($status) {
        'In Use' => ['bg-neutral-900 text-white border-neutral-900', 'bg-red-500'],
        'Under Repair' => ['bg-red-50 text-red-900 border-red-400', 'bg-red-600'],
        'Stored' => ['bg-neutral-100 text-neutral-900 border-neutral-400', 'bg-neutral-700'],
        'Disposed' => ['bg-red-100 text-red-950 border-red-600', 'bg-red-700'],
        default => ['bg-neutral-100 text-neutral-800 border-neutral-300', 'bg-neutral-500'],
    };
@endphp
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold tracking-wide border whitespace-nowrap {{ $badgeClasses }}">
    <span class="w-2 h-2 rounded-full shrink-0 {{ $dotClasses }}" aria-hidden="true"></span>
    <span>{{ $status }}</span>
</span>
