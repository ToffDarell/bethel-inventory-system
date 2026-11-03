@props([
    'type' => 'info', // 'success', 'warning', 'error', 'info'
    'title' => null,
    'message' => null,
])

@php
    $typeConfig = match ($type) {
        'success' => [
            'wrapper' => 'bg-emerald-50 border-emerald-200 text-emerald-900',
            'iconColor' => 'text-emerald-600',
            'title' => $title ?: 'Success',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'warning' => [
            'wrapper' => 'bg-amber-50 border-amber-200 text-amber-900',
            'iconColor' => 'text-amber-600',
            'title' => $title ?: 'Warning',
            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        'error', 'danger' => [
            'wrapper' => 'bg-red-50 border-red-200 text-red-900',
            'iconColor' => 'text-[#b91c1c]',
            'title' => $title ?: 'Error',
            'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        default => [
            'wrapper' => 'bg-sky-50 border-sky-200 text-sky-900',
            'iconColor' => 'text-[#0f5d8c]',
            'title' => $title ?: 'Notice',
            'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 5000)"
     class="flex items-start gap-3 p-4 rounded-xl border shadow-sm max-w-md w-full transition-all duration-200 {{ $typeConfig['wrapper'] }}"
     role="status">
    <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $typeConfig['iconColor'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeConfig['icon'] }}"/>
    </svg>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold">{{ $typeConfig['title'] }}</p>
        <div class="text-xs mt-0.5 leading-relaxed opacity-90">
            {{ $message ?: $slot }}
        </div>
    </div>
    <button type="button"
            onclick="this.closest('[role=status]').remove()"
            class="p-1 rounded-md opacity-60 hover:opacity-100 hover:bg-black/5 transition-opacity"
            aria-label="Dismiss">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
