@props([
    'label',
    'name',
    'type' => 'text',
    'required' => false,
    'value' => null,
    'placeholder' => null,
    'help' => null,
    'error' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-800">
        {{ $label }}
        @if ($required)
            <span class="text-red-600 font-bold ml-0.5">*</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="3"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors"
        >{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors"
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
            {{ $required ? 'required' : '' }}
            class="w-full px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors"
        />
    @endif

    @if ($help)
        <p class="text-xs text-slate-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
