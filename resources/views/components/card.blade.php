@props([
    'title' => null,
    'subtitle' => null,
    'header' => null,
    'footer' => null,
    'padding' => 'p-5 sm:p-6',
])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden transition-shadow']) }}>
    @if ($title || $header)
        <div class="px-5 py-4 sm:px-6 border-b border-slate-100 flex items-center justify-between gap-4">
            @if ($header)
                {{ $header }}
            @else
                <div>
                    <h3 class="text-base font-semibold text-slate-900 tracking-tight">{{ $title }}</h3>
                    @if ($subtitle)
                        <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-5 py-3 sm:px-6 bg-slate-50/80 border-t border-slate-100">
            {{ $footer }}
        </div>
    @endif
</div>
