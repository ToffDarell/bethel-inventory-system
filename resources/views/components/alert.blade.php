@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $classes = match ($type) {
        'success' => [
            'wrapper' => 'bg-status-success-bg border-status-success-border text-status-success-text',
            'icon_color' => 'text-status-success-dot',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'warning' => [
            'wrapper' => 'bg-status-warning-bg border-status-warning-border text-status-warning-text',
            'icon_color' => 'text-status-warning-dot',
            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        'error' => [
            'wrapper' => 'bg-red-50 border-red-200 text-red-950',
            'icon_color' => 'text-brand-red',
            'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        default => [
            'wrapper' => 'bg-slate-100 border-slate-200 text-slate-800',
            'icon_color' => 'text-primary',
            'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-xl border flex items-start gap-3 shadow-2xs {$classes['wrapper']}"]) }} role="alert">
    <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $classes['icon_color'] }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $classes['icon'] }}"/>
    </svg>
    <div class="flex-1 text-xs leading-relaxed">
        @if ($title)
            <p class="font-semibold text-sm mb-1">{{ $title }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
