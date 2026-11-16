@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <a href="{{ route('settings.index', ['tab' => 'asset-templates']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-2xs transition-colors mb-2.5">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back to Settings &gt; Asset Templates</span>
                </a>
                <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Asset Templates Import Preview</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Review parsed equipment templates, duplicates, and category matching before adding them to the masterlist
                </p>
            </div>

            <div class="flex items-center gap-3">
                <x-button variant="secondary" :href="route('settings.index', ['tab' => 'asset-templates'])">
                    Cancel
                </x-button>

                @if ($valid_count > 0)
                    <form method="POST" action="{{ route('settings.asset-templates.import.commit') }}">
                        @csrf
                        <x-button variant="primary" type="submit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Confirm &amp; Import {{ $valid_count }} {{ Str::plural('Template', $valid_count) }}</span>
                        </x-button>
                    </form>
                @endif
            </div>
        </div>

        @if (!empty($unknown_category_count) && $unknown_category_count > 0)
            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-950 text-xs leading-relaxed">
                <p class="font-semibold text-amber-900">Unknown Categories Flagged ({{ $unknown_category_count }})</p>
                <p class="text-amber-800 mt-0.5">
                    Unknown categories are not auto-created. Rows marked "Category not found" will be imported with a blank default category so you can assign them cleanly later.
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-5 rounded-xl border border-emerald-200 bg-emerald-50/60 shadow-2xs">
                <p class="text-xs font-semibold text-emerald-800">Ready to import</p>
                <p class="text-3xl font-semibold text-emerald-950 tabular-nums mt-2">{{ $valid_count }}</p>
                <p class="text-xs text-emerald-700 mt-2">New unique templates ready for the masterlist</p>
            </div>

            <div class="p-5 rounded-xl border border-amber-200 bg-amber-50/60 shadow-2xs">
                <p class="text-xs font-semibold text-amber-800">Duplicate (skipped)</p>
                <p class="text-3xl font-semibold text-amber-950 tabular-nums mt-2">{{ $duplicate_count }}</p>
                <p class="text-xs text-amber-700 mt-2">Matching Name + Brand + Model already exist</p>
            </div>

            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50 shadow-2xs">
                <p class="text-xs font-semibold text-slate-600">Invalid rows (skipped)</p>
                <p class="text-3xl font-semibold text-slate-900 tabular-nums mt-2">{{ $invalid_count }}</p>
                <p class="text-xs text-slate-500 mt-2">Missing required template name</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Parsed Template Rows</h2>
                    <p class="text-xs text-slate-500 mt-0.5">All non-duplicate templates are saved without monetary or depreciation values</p>
                </div>
                <span class="text-xs font-mono text-slate-500 tabular-nums">
                    Total {{ count($rows) }} {{ Str::plural('row', count($rows)) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th class="py-3 px-4 w-14 text-center">Row</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Equipment Name</th>
                            <th class="py-3 px-4">Brand</th>
                            <th class="py-3 px-4">Model</th>
                            <th class="py-3 px-4">Default Category</th>
                            <th class="py-3 px-4">Default Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $rowClass = $row['is_valid']
                                    ? 'bg-emerald-50/40 text-slate-900'
                                    : ($row['is_duplicate'] ? 'bg-amber-50/40 text-slate-700' : 'bg-slate-50/60 text-slate-500');
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="py-3 px-4 text-center font-mono text-xs text-slate-500 tabular-nums">{{ $row['row_number'] }}</td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($row['is_valid'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">{{ $row['status_label'] }}</span>
                                    @elseif ($row['is_duplicate'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">{{ $row['status_label'] }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-700 border border-slate-300">{{ $row['status_label'] }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-semibold">{{ $row['name'] ?: 'Missing name' }}</td>
                                <td class="py-3 px-4 text-slate-700">{{ $row['brand'] ?: '-' }}</td>
                                <td class="py-3 px-4 text-slate-700">{{ $row['model'] ?: '-' }}</td>
                                <td class="py-3 px-4">
                                    @if (!empty($row['is_unknown_category']))
                                        <div class="text-slate-700 line-through text-xs">{{ $row['category_display'] }}</div>
                                        <span class="inline-flex items-center px-2 py-0.5 mt-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            Category not found (left blank)
                                        </span>
                                    @elseif (!empty($row['category_display']))
                                        <span class="text-slate-800 font-medium">{{ $row['category_display'] }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">Blank</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600 max-w-xs truncate" title="{{ $row['default_remarks'] ?? '' }}">{{ $row['default_remarks'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-500">No template rows found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
