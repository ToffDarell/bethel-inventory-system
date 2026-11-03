@props([
    'title',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => 'Back',
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-4 mb-6']) }}>
    <div>
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:underline mb-1.5 transition-colors">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>{{ $backLabel }}</span>
            </a>
        @endif

        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-sm text-slate-600 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
