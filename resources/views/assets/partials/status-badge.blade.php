@php
    [$badgeClasses, $dotClasses] = match ($status) {
        'In Use' => ['bg-red-600 text-white border-red-600', 'bg-white'],
        'Under Repair' => ['bg-red-50 text-red-700 border-red-300', 'bg-red-600'],
        'Stored' => ['bg-white text-red-700 border-red-300', 'bg-red-500'],
        'Disposed' => ['bg-red-100 text-red-800 border-red-400', 'bg-red-700'],
        default => ['bg-red-50 text-red-700 border-red-200', 'bg-red-600'],
    };
@endphp
<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold tracking-wide border whitespace-nowrap {{ $badgeClasses }}">
    <span class="w-2 h-2 rounded-full shrink-0 {{ $dotClasses }}" aria-hidden="true"></span>
    <span>{{ $status }}</span>
</span>
