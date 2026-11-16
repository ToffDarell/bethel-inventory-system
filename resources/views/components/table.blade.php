@props([
    'striped' => false,
])

<div {{ $attributes->merge(['class' => 'relative bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs min-w-0 max-w-full']) }}>
    <div class="overflow-x-auto min-w-0 max-w-full">
        <table class="w-full text-left border-collapse text-[13.5px]">
            <thead class="bg-slate-50/95 sticky top-0 z-10 border-b border-slate-200 text-xs font-semibold text-slate-700 select-none">
                {{ $header ?? '' }}
            </thead>
            <tbody class="divide-y divide-slate-100 {{ $striped ? '[&>tr:nth-child(even)]:bg-slate-50/40' : '' }} [&>tr]:h-[52px] [&>tr:hover]:bg-slate-50/90 transition-colors">
                {{ $slot }}
            </tbody>
            @if (isset($tfoot))
                <tfoot class="border-t-2 border-slate-300">
                    {{ $tfoot }}
                </tfoot>
            @endif
        </table>
    </div>

    @if (isset($footer))
        <div class="px-5 py-3 sm:px-6 bg-slate-50 border-t border-slate-100">
            {{ $footer }}
        </div>
    @endif
</div>
