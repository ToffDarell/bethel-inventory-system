@props([
    'striped' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-[15px]">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-600">
                {{ $header ?? '' }}
            </thead>
            <tbody class="divide-y divide-slate-100 {{ $striped ? '[&>tr:nth-child(even)]:bg-slate-50/50' : '' }} [&>tr:hover]:bg-sky-50/40 transition-colors">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if (isset($footer))
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100">
            {{ $footer }}
        </div>
    @endif
</div>
