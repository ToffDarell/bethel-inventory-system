@props([
    'label',
    'name',
    'type' => 'text',
    'required' => false,
    'value' => null,
    'placeholder' => null,
    'help' => null,
    'mono' => false,
    'list' => null,
    'autofocus' => false,
])

@php
    $hasError = $errors->has($name);
    $borderClasses = $hasError
        ? 'border-red-500 focus:border-red-600 focus:ring-red-600'
        : 'border-slate-300 focus:border-primary focus:ring-primary';
    $fontClasses = $mono ? 'font-mono' : '';
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <div class="flex items-center justify-between gap-2">
        <label for="{{ $name }}" class="block text-sm font-semibold text-slate-800">
            {{ $label }}
            @if ($required)
                <span class="text-brand-red font-semibold ml-0.5" aria-hidden="true">*</span>
            @else
                <span class="text-xs font-normal text-slate-400 ml-1">(Optional)</span>
            @endif
        </label>
        @if (isset($labelRight))
            {{ $labelRight }}
        @endif
    </div>

    @if ($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="3"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $autofocus ? 'autofocus' : '' }}
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if($help) aria-describedby="{{ $name }}-help" @endif
            class="w-full px-3.5 py-2 text-sm bg-white border {{ $borderClasses }} {{ $fontClasses }} rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:ring-1 transition-colors"
        >{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $autofocus ? 'autofocus' : '' }}
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if($help) aria-describedby="{{ $name }}-help" @endif
            class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border {{ $borderClasses }} rounded-lg shadow-2xs focus:outline-none focus:ring-1 transition-colors"
        >
            {{ $slot }}
        </select>
    @else
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($list) list="{{ $list }}" @endif
            {{ $required ? 'required' : '' }}
            {{ $autofocus ? 'autofocus' : '' }}
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            @if($help) aria-describedby="{{ $name }}-help" @endif
            class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border {{ $borderClasses }} {{ $fontClasses }} rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:ring-1 transition-colors"
        />
    @endif

    @if ($help)
        <p id="{{ $name }}-help" class="text-xs text-slate-500 leading-relaxed">{{ $help }}</p>
    @endif

    @error($name)
        <p class="text-xs font-medium text-brand-red flex items-center gap-1.5 mt-1" role="alert">
            <svg class="w-3.5 h-3.5 shrink-0 text-brand-red" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
