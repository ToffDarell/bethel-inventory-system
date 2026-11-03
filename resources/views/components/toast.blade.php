@props([
    'type' => 'success',
    'message' => null,
])

@php
    $config = match ($type) {
        'success' => [
            'wrapper' => 'bg-status-success-bg border-status-success-border text-status-success-text',
            'iconColor' => 'text-status-success-dot',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'warning' => [
            'wrapper' => 'bg-status-warning-bg border-status-warning-border text-status-warning-text',
            'iconColor' => 'text-status-warning-dot',
            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        'error' => [
            'wrapper' => 'bg-red-50 border-red-200 text-red-950',
            'iconColor' => 'text-brand-red',
            'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        default => [
            'wrapper' => 'bg-slate-100 border-slate-200 text-slate-800',
            'iconColor' => 'text-primary',
            'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 5000)"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform -translate-y-2"
     class="p-4 rounded-xl border flex items-center justify-between gap-3 shadow-2xs mb-4 {{ $config['wrapper'] }}"
     role="status">
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0 {{ $config['iconColor'] }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['icon'] }}"/>
        </svg>
        <p class="text-xs font-medium">{{ $message ?? $slot }}</p>
    </div>
    <button @click="show = false"
            type="button"
            class="text-slate-400 hover:text-slate-600 p-1 rounded-md transition-colors cursor-pointer"
            aria-label="Dismiss notification">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
