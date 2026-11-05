@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Header with Back & Actions -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 hover:underline mb-1.5 transition-colors">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back to Reports &amp; Import</span>
                </a>
                <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Import Preview</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Review parsed spreadsheet rows and duplicate checks before committing to the inventory register
                </p>
            </div>

            <div class="flex items-center gap-3">
                <x-button variant="secondary" :href="route('reports.index')">
                    Cancel
                </x-button>

                @if ($valid_count > 0)
                    <form method="POST" action="{{ route('reports.import.commit') }}">
                        @csrf
                        <x-button variant="primary" type="submit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Confirm &amp; Import {{ $valid_count }} {{ Str::plural('Asset', $valid_count) }}</span>
                        </x-button>
                    </form>
                @endif
            </div>
        </div>

        <!-- 4-Step Stepper (Step 3 Active, Step 4 Ready) -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-200 text-emerald-950">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div>
                        <p class="font-medium text-slate-900">1. Download Template</p>
                        <p class="text-[11px] text-emerald-700">Completed</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-200 text-emerald-950">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div>
                        <p class="font-medium text-slate-900">2. Upload File</p>
                        <p class="text-[11px] text-emerald-700">Parsed successfully</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-sky-50/70 border border-sky-200 text-slate-900">
                    <span class="w-6 h-6 rounded-full bg-primary text-white font-semibold flex items-center justify-center shrink-0">3</span>
                    <div>
                        <p class="font-semibold text-slate-900">3. Review Preview</p>
                        <p class="text-[11px] text-slate-600">Active inspection</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 font-semibold flex items-center justify-center shrink-0">4</span>
                    <div>
                        <p class="font-medium text-slate-900">4. Confirm &amp; Import</p>
                        <p class="text-[11px] text-slate-500">Ready to save</p>
                    </div>
                </div>
            </div>
        </div>

        @if (!empty($unknown_category_count) && $unknown_category_count > 0)
            <div class="p-4 rounded-xl border border-sky-200 bg-sky-50 text-sky-950 flex items-start gap-3 text-xs leading-relaxed">
                <svg class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
                <div>
                    <p class="font-semibold text-sky-900">New Categories Detected ({{ $unknown_category_count }})</p>
                    <p class="text-sky-800 mt-0.5">
                        Some rows reference categories that are not currently registered. These categories will be created automatically in Settings upon confirming the import.
                    </p>
                </div>
            </div>
        @endif

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Ready to Import -->
            <div class="p-5 rounded-xl border border-emerald-200 bg-emerald-50/60 shadow-2xs">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold text-emerald-800">Ready to import</p>
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                </div>
                <p class="text-3xl font-semibold text-emerald-950 tabular-nums mt-2">{{ $valid_count }}</p>
                <p class="text-xs text-emerald-700 mt-2">Valid equipment rows ready to register</p>
            </div>

            <!-- Duplicate Asset No. (Skipped) -->
            <div class="p-5 rounded-xl border border-amber-200 bg-amber-50/60 shadow-2xs">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold text-amber-800">Duplicate (skipped)</p>
                    <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                </div>
                <p class="text-3xl font-semibold text-amber-950 tabular-nums mt-2">{{ $duplicate_count }}</p>
                <p class="text-xs text-amber-700 mt-2">Asset numbers already present in database</p>
            </div>

            <!-- Incomplete Rows (Skipped) -->
            <div class="p-5 rounded-xl border border-slate-200 bg-slate-50 shadow-2xs">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold text-slate-600">Incomplete rows (skipped)</p>
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                </div>
                <p class="text-3xl font-semibold text-slate-900 tabular-nums mt-2">{{ $invalid_count }}</p>
                <p class="text-xs text-slate-500 mt-2">Missing mandatory asset name or status</p>
            </div>
        </div>

        <!-- Preview Table with Green, Amber, and Gray Row States -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Parsed Spreadsheet Rows</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Green indicates valid rows to import; amber and gray indicate rows that will be skipped</p>
                </div>
                <span class="text-xs font-mono text-slate-500 tabular-nums">
                    Total {{ count($rows) }} {{ Str::plural('row', count($rows)) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-[15px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th class="py-3 px-4 w-14 text-center">Row</th>
                            <th class="py-3 px-4">Validation State</th>
                            <th class="py-3 px-4">Asset No.</th>
                            <th class="py-3 px-4">Asset Description</th>
                            <th class="py-3 px-4">Brand &amp; Model</th>
                            <th class="py-3 px-4">Serial Number</th>
                            <th class="py-3 px-4">Department &amp; Section</th>
                            <th class="py-3 px-4">Location &amp; Personnel</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4 whitespace-nowrap">Year Bought</th>
                            <th class="py-3 px-4 whitespace-nowrap">Asset Condition</th>
                            <th class="py-3 px-4">Status</th>
                            @if (auth()->user()?->isAdmin())
                                <th class="py-3 px-4 text-right">Cost</th>
                            @endif
                            <th class="py-3 px-4">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $rowClass = $row['is_valid']
                                    ? 'bg-emerald-50/40 hover:bg-emerald-50/70 text-slate-900'
                                    : ($row['is_duplicate']
                                        ? 'bg-amber-50/40 hover:bg-amber-50/70 text-slate-700'
                                        : 'bg-slate-50/60 hover:bg-slate-100/60 text-slate-500');
                                $yearBoughtDisplay = !empty($row['date_acquired'])
                                    ? (strlen($row['date_acquired']) >= 4 ? substr($row['date_acquired'], 0, 4) : $row['date_acquired'])
                                    : 'Not set';
                            @endphp
                            <tr class="{{ $rowClass }} transition-colors">
                                <td class="py-3 px-4 text-center font-mono text-xs text-slate-500 tabular-nums">
                                    {{ $row['row_number'] }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($row['is_valid'])
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            <span>{{ $row['status_label'] }}</span>
                                        </span>
                                    @elseif ($row['is_duplicate'])
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            <span>{{ $row['status_label'] }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-700 border border-slate-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                            <span>{{ $row['status_label'] }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-xs whitespace-nowrap">
                                    @if ($row['asset_no'] !== '')
                                        <span class="font-medium text-slate-900">{{ $row['asset_no'] }}</span>
                                    @else
                                        <span class="text-xs font-sans text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                            Auto-generate
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-900">
                                    {{ $row['asset_name'] ?: 'Not specified' }}
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-700">
                                    @if ($row['brand'] || $row['model'])
                                        <div class="font-medium text-slate-900">{{ $row['brand'] ?: 'No brand' }}</div>
                                        @if ($row['model'])
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $row['model'] }}</div>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400">Not specified</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-600">
                                    {{ $row['serial_no'] ?: 'None' }}
                                </td>
                                <td class="py-3 px-4 text-sm">
                                    @if ($row['department'] || $row['section'])
                                        <div class="font-medium text-slate-900">{{ $row['department'] ?: 'Unassigned' }}</div>
                                        @if ($row['section'])
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $row['section'] }}</div>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400">Unassigned</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-sm">
                                    @if ($row['location'] || $row['assigned_personnel'])
                                        <div class="font-medium text-slate-900">{{ $row['location'] ?: 'No room' }}</div>
                                        @if ($row['assigned_personnel'])
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $row['assigned_personnel'] }}</div>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400">Unassigned</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-sm">
                                    @if (!empty($row['category']))
                                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-800">
                                            <span>{{ $row['category'] }}</span>
                                            @if (!empty($row['is_unknown_category']))
                                                <span class="text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded bg-sky-100 text-sky-800 font-semibold" title="Will be auto-created">New</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">Uncategorized</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-700 font-mono tabular-nums whitespace-nowrap">
                                    {{ $yearBoughtDisplay }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($row['asset_condition'])
                                        <x-condition-badge :condition="$row['asset_condition']" />
                                        @if (!empty($row['condition_mapped']))
                                            <div class="text-[10px] text-sky-700 font-medium mt-1">
                                                Mapped from "{{ $row['condition_original'] }}"
                                            </div>
                                        @endif
                                    @elseif (!empty($row['is_unknown_condition']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-300" title="{{ $row['condition_note'] }}">
                                            <svg class="w-3 h-3 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>Unknown: "{{ $row['condition_original'] }}"</span>
                                        </span>
                                        <div class="text-[10px] text-slate-500 mt-0.5">Saved as Not set</div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            Not set
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <x-badge :status="$row['status'] ?: 'In Use'" />
                                </td>
                                @if (auth()->user()?->isAdmin())
                                    <td class="py-3 px-4 text-sm font-mono tabular-nums text-right whitespace-nowrap">
                                        @if (isset($row['cost']) && $row['cost'] !== null)
                                            <span class="text-slate-900 font-medium">₱{{ number_format((float) $row['cost'], 2) }}</span>
                                        @else
                                            <span class="text-xs font-sans text-slate-400">No cost entered</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="py-3 px-4 text-xs text-slate-600 max-w-[200px] truncate" title="{{ $row['notes'] }}">
                                    {{ $row['notes'] ?: 'None' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="p-8 text-center text-slate-500">
                                    No data rows were found in the uploaded spreadsheet file.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
