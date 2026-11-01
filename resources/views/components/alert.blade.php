@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $config = match ($type) {
        'success' => [
            'wrapper' => 'bg-emerald-50 border-emerald-200 text-emerald-950',
            'icon_color' => 'text-emerald-700',
            'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'warning' => [
            'wrapper' => 'bg-amber-50 border-amber-300 text-amber-950',
            'icon_color' => 'text-amber-700',
            'path' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        'error' => [
            'wrapper' => 'bg-red-50 border-red-200 text-red-950',
            'icon_color' => 'text-red-700',
            'path' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        default => [
            'wrapper' => 'bg-sky-50 border-sky-200 text-sky-950',
            'icon_color' => 'text-[#0f5d8c]',
            'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-xl border flex items-start gap-3 shadow-2xs {$config['wrapper']}"]) }} role="alert">
    <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $config['icon_color'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['path'] }}" />
    </svg>

    <div class="flex-1 text-sm leading-relaxed">
        @if ($title)
            <p class="font-bold mb-0.5 text-inherit">{{ $title }}</p>
        @endif
        <div class="text-inherit">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button type="button"
                onclick="this.closest('[role=alert]').remove()"
                aria-label="Dismiss"
                class="p-1 rounded-lg hover:bg-black/5 text-inherit opacity-70 hover:opacity-100 transition cursor-pointer shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
