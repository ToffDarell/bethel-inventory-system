@php
    $styles = match ($status) {
        'In Use' => 'bg-emerald-100 text-emerald-900 border-emerald-400',
        'Under Repair' => 'bg-amber-100 text-amber-900 border-amber-400',
        'Stored' => 'bg-blue-100 text-blue-900 border-blue-400',
        'Disposed' => 'bg-red-100 text-red-900 border-red-400',
        default => 'bg-slate-100 text-slate-800 border-slate-300',
    };
@endphp
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold border {{ $styles }}">
    {{ $status }}
</span>
