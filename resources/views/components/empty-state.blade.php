@props([
    'title' => 'No items found',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-6 bg-white border border-dashed border-slate-200 rounded-xl']) }}>
    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
        {{ $icon ?? null ?: '' }}
        @if (! isset($icon))
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        @endif
    </div>
    <h3 class="text-base font-bold text-slate-900 mb-1">{{ $title }}</h3>
    @if ($description)
        <p class="text-sm text-slate-500 max-w-md mx-auto mb-5">{{ $description }}</p>
    @endif
    @if (isset($action))
        <div class="mt-4 flex justify-center">
            {{ $action }}
        </div>
    @endif
</div>
