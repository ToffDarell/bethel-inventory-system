@extends('layouts.app')

@section('content')
    <style>
        .dep-print-sheet {
            display: none;
        }
        .dep-print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5px;
            line-height: 1.3;
            color: #0f172a;
        }
        .dep-print-table th {
            background-color: #dbeafe;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            padding: 4px 5px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: left;
            color: #0f172a;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .dep-print-table td {
            padding: 2.5px 5px;
            vertical-align: top;
            overflow-wrap: break-word;
            border-bottom: 0.5px solid #e2e8f0;
        }
        .dep-print-num {
            text-align: right !important;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .dep-print-center {
            text-align: center !important;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 10mm 12mm 10mm;
            }
            body {
                background: #ffffff !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            header,
            nav,
            footer,
            .no-print,
            .dep-screen-only {
                display: none !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .dep-print-sheet {
                display: block !important;
                width: 100% !important;
            }
            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>

    <div class="dep-screen-only space-y-6">
        <!-- Page Header -->
        <x-page-header
            title="Depreciation Report"
            subtitle="Track purchase cost, value lost over time, and what hospital equipment is worth today"
            :backUrl="route('reports.index')"
            backLabel="Back to Reports"
        >
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <a href="{{ route('reports.depreciation.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                       class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition-colors">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Raw data (CSV)</span>
                    </a>
                    <a href="{{ route('reports.depreciation.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                       class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs shadow-2xs transition-colors">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Export Excel (.xlsx)</span>
                    </a>
                    <button type="button"
                            onclick="window.print()"
                            class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition-colors cursor-pointer">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/>
                        </svg>
                        <span>Print Report</span>
                    </button>
                    <a href="{{ route('reports.depreciation.export-pdf', request()->query()) }}"
                       class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition-colors">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <span>Export PDF</span>
                    </a>
                </div>
            </x-slot:actions>
        </x-page-header>

        <!-- Disclaimer & Accounting Notice Banner -->
        <div class="p-3.5 bg-amber-50/90 border border-amber-200 rounded-xl flex flex-wrap items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span class="text-xs font-semibold text-amber-950">
                    Estimated values for tracking, not an official accounting record
                </span>
            </div>
            @if (($policyMinCost ?? 0) > 0)
                <span class="text-[11px] font-medium text-amber-800 bg-amber-100/70 px-2.5 py-0.5 rounded-full">
                    Minimum cost threshold: ₱{{ number_format($policyMinCost, 2) }}
                </span>
            @endif
        </div>

        <!-- Valuation Totals Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Acquisition Cost</span>
                <p class="text-2xl font-extrabold text-slate-900 tabular-nums mt-1">
                    ₱{{ number_format($totalCost, 2) }}
                </p>
                <span class="text-xs text-slate-400 mt-0.5 block">What the hospital paid</span>
            </div>

            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Accumulated Depreciation</span>
                <p class="text-2xl font-extrabold text-amber-700 tabular-nums mt-1">
                    ₱{{ number_format($totalAccumulated, 2) }}
                </p>
                <span class="text-xs text-slate-400 mt-0.5 block">Value lost so far</span>
            </div>

            <div class="p-5 rounded-xl bg-slate-900 text-white border border-slate-800 shadow-2xs">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Net Book Value</span>
                <p class="text-2xl font-extrabold text-white tabular-nums mt-1">
                    ₱{{ number_format($totalCurrentValue, 2) }}
                </p>
                <span class="text-xs text-slate-400 mt-0.5 block">What all equipment is worth today</span>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <x-card padding="p-4">
            <form method="GET" action="{{ route('reports.depreciation') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <div class="sm:col-span-4">
                    <label for="filter_category" class="block text-xs font-semibold text-slate-700 mb-1">
                        Category
                    </label>
                    <select id="filter_category"
                            name="category"
                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($selectedCategory == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-4">
                    <label for="filter_department" class="block text-xs font-semibold text-slate-700 mb-1">
                        Department
                    </label>
                    <select id="filter_department"
                            name="department"
                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}" @selected($selectedDepartment == $dept)>
                                {{ $dept }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="filter_as_of" class="block text-xs font-semibold text-slate-700 mb-1">
                        As of Date
                    </label>
                    <input type="date" id="filter_as_of" name="as_of" value="{{ request('as_of') ?: $asOfDate->format('Y-m-d') }}" class="w-full min-h-[44px] px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                </div>

                <div class="sm:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 min-h-[44px] inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer">
                        Filter
                    </button>
                    @if ($selectedCategory || $selectedDepartment || request('as_of'))
                        <a href="{{ route('reports.depreciation') }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- By Category Summary Table -->
        <x-card title="Category Valuation Summary" subtitle="Total equipment value grouped by category">
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-center">Active Assets</th>
                        <th class="py-3 px-4 text-right">Total Cost</th>
                        <th class="py-3 px-4 text-right">Accumulated Depreciation</th>
                        <th class="py-3 px-4 text-right">Current Value</th>
                    </tr>
                </x-slot:header>

                @forelse ($categorySummary as $summary)
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            {{ $summary['name'] }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if ($summary['without_cost_count'] > 0)
                                <span class="text-xs font-semibold text-slate-800 tabular-nums">
                                    {{ $summary['cost_count'] }} ({{ $summary['without_cost_count'] }} without cost)
                                </span>
                            @else
                                <span class="text-xs font-semibold text-slate-700 tabular-nums">
                                    {{ $summary['cost_count'] }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right text-xs text-slate-800 tabular-nums">
                            ₱{{ number_format($summary['cost'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-xs text-amber-800 tabular-nums">
                            ₱{{ number_format($summary['accumulated'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-xs font-bold text-slate-900 tabular-nums">
                            ₱{{ number_format($summary['current_value'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-500">No assets found matching the selected filters.</td>
                    </tr>
                @endforelse
            </x-table>
        </x-card>

        <!-- Asset-by-Asset Depreciation Table -->
        <x-card title="Asset Values" subtitle="Cost, value lost, and current worth for each piece of equipment" padding="p-4 sm:p-5">
            <!-- Method and Parameters Note -->
            <div class="mb-4 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>
                        <strong>Method:</strong> Straight-line &bull;
                        <strong>As-of Date:</strong> {{ $asOfDate->format('F j, Y') }} &bull;
                        <strong>Assumed Start Date:</strong> {{ $policyConventionLabel ?? 'Counted from January 1 of the Year Bought' }}
                        @if (($policyMinCost ?? 0) > 0)
                            &bull; <strong>Minimum Cost:</strong> ₱{{ number_format($policyMinCost, 2) }}
                        @endif
                    </span>
                </div>
                <span class="text-xs text-slate-500">Calculates an equal drop in value each year across the equipment's lifespan</span>
            </div>

            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="py-3 px-3.5 whitespace-nowrap">Asset No.</th>
                        <th class="py-3 px-3.5">Asset Description</th>
                        <th class="py-3 px-3.5 whitespace-nowrap">Category</th>
                        <th class="py-3 px-3.5 text-center whitespace-nowrap">Year Bought</th>
                        <th class="py-3 px-3.5 text-center whitespace-nowrap" title="Expected lifespan in years">Useful Life</th>
                        <th class="py-3 px-3.5 text-right whitespace-nowrap" title="What the hospital paid">Cost</th>
                        <th class="py-3 px-3.5 text-right whitespace-nowrap" title="Expected residual value at the end of its useful life">Disposal Value</th>
                        <th class="py-3 px-3.5 text-right whitespace-nowrap" title="Total value lost so far">Accumulated</th>
                        <th class="py-3 px-3.5 text-right whitespace-nowrap" title="Estimated worth today">Current Value</th>
                    </tr>
                </x-slot:header>

                @forelse ($assets as $asset)
                    @php
                        $dep = $depreciationService->calculateForAsset($asset, $asOfDate);
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-3.5 font-mono text-xs font-semibold text-slate-900 whitespace-nowrap">
                            <a href="{{ route('assets.show', $asset) }}" class="hover:text-primary hover:underline">
                                {{ $asset->asset_no }}
                            </a>
                        </td>
                        <td class="py-3 px-3.5 font-medium text-slate-900">
                            {{ $asset->asset_name }}
                        </td>
                        <td class="py-3 px-3.5 text-xs text-slate-700 whitespace-nowrap">
                            {{ $asset->category?->name ?? 'Uncategorized' }}
                        </td>
                        <td class="py-3 px-3.5 text-center text-xs text-slate-700 whitespace-nowrap tabular-nums">
                            {{ $asset->date_acquired ? $asset->date_acquired->format('Y') : 'Not recorded' }}
                        </td>
                        <td class="py-3 px-3.5 text-center text-xs text-slate-700 whitespace-nowrap tabular-nums">
                            {{ $dep->isCalculable() ? $dep->getUsefulLifeYears() . ' yrs' : ($asset->category?->default_useful_life_years ? $asset->category->default_useful_life_years . ' yrs' : '-') }}
                        </td>
                        @if ($asset->cost !== null)
                            <td class="py-3 px-3.5 text-right text-xs text-slate-800 whitespace-nowrap tabular-nums">
                                ₱{{ number_format($asset->cost, 2) }}
                            </td>
                            <td class="py-3 px-3.5 text-right text-xs text-slate-700 whitespace-nowrap tabular-nums">
                                {{ $dep->isCalculable() && $dep->getDisposalValue() !== null ? '₱' . number_format($dep->getDisposalValue(), 2) : '-' }}
                            </td>
                            @if ($dep->getMissingReason() === 'Below minimum cost')
                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200" title="Asset cost is below the ₱{{ number_format($policyMinCost, 2) }} depreciation threshold">
                                        Below minimum cost
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                    <span class="text-xs text-slate-400">Below minimum cost</span>
                                </td>
                            @elseif ($dep->getMissingReason() === 'Select a category to calculate')
                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Select a category to calculate
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                    <span class="text-xs text-slate-500 font-mono">₱{{ number_format($asset->cost, 2) }}</span>
                                </td>
                            @elseif ($dep->getMissingReason() === 'Useful life not set')
                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Useful life not set
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                    <span class="text-xs text-slate-500 font-mono">₱{{ number_format($asset->cost, 2) }}</span>
                                </td>
                            @else
                                <td class="py-3 px-3.5 text-right text-xs text-amber-800 whitespace-nowrap tabular-nums">
                                    {{ $dep->isCalculable() ? '₱' . number_format($dep->getAccumulatedDepreciation(), 2) : '-' }}
                                </td>
                                <td class="py-3 px-3.5 text-right text-xs font-bold text-slate-900 whitespace-nowrap tabular-nums">
                                    {{ $dep->isCalculable() ? '₱' . number_format($dep->getCurrentValue(), 2) : '₱' . number_format($asset->cost, 2) }}
                                </td>
                            @endif
                        @else
                            <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('assets.edit', $asset) }}"
                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 hover:text-amber-800 hover:border-amber-300 transition-colors"
                                   title="Click to edit and enter asset cost">
                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span>Missing cost</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </td>
                            <td class="py-3 px-3.5 text-right text-xs text-slate-400 whitespace-nowrap tabular-nums">
                                -
                            </td>
                            <td class="py-3 px-3.5 text-right text-xs text-slate-400 whitespace-nowrap tabular-nums">
                                -
                            </td>
                            <td class="py-3 px-3.5 text-right text-xs text-slate-400 whitespace-nowrap tabular-nums">
                                -
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-500">No assets found matching the selected filters.</td>
                    </tr>
                @endforelse

                <!-- Table Totals Row -->
                <x-slot:tfoot>
                    <tr class="bg-slate-100/90 font-semibold border-t-2 border-slate-300">
                        <td colspan="5" class="py-3.5 px-3.5 text-slate-900 uppercase tracking-wider text-xs font-bold">
                            Totals
                        </td>
                        <td class="py-3.5 px-3.5 text-right text-xs font-bold text-slate-900 whitespace-nowrap tabular-nums">
                            ₱{{ number_format($totalCost, 2) }}
                        </td>
                        <td class="py-3.5 px-3.5 text-right text-xs font-bold text-slate-700 whitespace-nowrap tabular-nums">
                            ₱{{ number_format($totalSalvage, 2) }}
                        </td>
                        <td class="py-3.5 px-3.5 text-right text-xs font-bold text-amber-900 whitespace-nowrap tabular-nums">
                            ₱{{ number_format($totalAccumulated, 2) }}
                        </td>
                        <td class="py-3.5 px-3.5 text-right text-xs font-bold text-primary whitespace-nowrap tabular-nums">
                            ₱{{ number_format($totalCurrentValue, 2) }}
                        </td>
                    </tr>
                </x-slot:tfoot>
            </x-table>

            <!-- Excluded Assets Note -->
            @if ($missingCostCount > 0 || ($belowMinCostCount ?? 0) > 0)
                <div class="mt-3.5 flex flex-col sm:flex-row sm:items-center gap-2 text-xs text-slate-600 bg-slate-50 border border-slate-200/80 rounded-lg p-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>
                            @if ($missingCostCount > 0)
                                <strong>Note:</strong> {{ $missingCostCount }} {{ \Illuminate\Support\Str::plural('asset', $missingCostCount) }} excluded from totals because no cost was entered.
                            @endif
                            @if (($belowMinCostCount ?? 0) > 0)
                                {{ $missingCostCount > 0 ? 'Also,' : '<strong>Note:</strong>' }} {{ $belowMinCostCount }} {{ \Illuminate\Support\Str::plural('asset', $belowMinCostCount) }} excluded from totals because cost is below the ₱{{ number_format($policyMinCost, 2) }} minimum depreciation threshold.
                            @endif
                        </span>
                    </div>
                </div>
            @endif

            <div class="mt-4">
                {{ $assets->links() }}
            </div>
        </x-card>
    </div>

    <!-- Clean Print Layout for Depreciation Summary (Shown only when printing via window.print()) -->
    <div class="dep-print-sheet">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding-bottom: 10px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                @if (! empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" alt="Hospital Logo" style="height: 48px; width: auto; object-fit: contain;">
                @endif
                <div>
                    <div style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: #0f172a; line-height: 1.2;">
                        {{ $hospitalName ?? 'BETHEL BAPTIST HOSPITAL, INC.' }}
                    </div>
                    <div style="font-size: 11px; color: #334155; line-height: 1.35;">{{ $hospitalAddress ?? '' }}</div>
                    <div style="font-size: 11px; color: #334155; line-height: 1.35;">Tel. No. : (088) 813-2229 / (088) 813-2708</div>
                </div>
            </div>

            <div style="text-align: right; font-size: 11px; color: #334155; line-height: 1.4;">
                <div>As of Date : <strong style="color: #0f172a;">{{ $asOfDate->format('M d, Y') }}</strong></div>
                <div>Date Printed : {{ now()->format('n/j/Y g:i:s A') }}</div>
            </div>
        </div>

        <div style="margin-top: 4px; margin-bottom: 10px;">
            <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #1e3a8a; text-decoration: underline;">
                DEPRECIATION SUMMARY REPORT
            </span>
        </div>

        <!-- Category Valuation Summary Table -->
        <div style="margin-bottom: 14px;">
            <div style="margin-bottom: 4px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #dc2626;">
                CATEGORY VALUATION SUMMARY
            </div>
            <table class="dep-print-table">
                <colgroup>
                    <col style="width: 34%;">
                    <col style="width: 14%;">
                    <col style="width: 18%;">
                    <col style="width: 17%;">
                    <col style="width: 17%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>CATEGORY</th>
                        <th class="dep-print-center">ACTIVE ASSETS</th>
                        <th class="dep-print-num">TOTAL COST</th>
                        <th class="dep-print-num">ACCUMULATED</th>
                        <th class="dep-print-num">CURRENT VALUE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categorySummary as $summary)
                        <tr>
                            <td style="font-weight: 700;">{{ $summary['name'] }}</td>
                            <td class="dep-print-center">{{ $summary['cost_count'] }}</td>
                            <td class="dep-print-num">{{ number_format($summary['cost'], 2) }}</td>
                            <td class="dep-print-num">{{ number_format($summary['accumulated'], 2) }}</td>
                            <td class="dep-print-num" style="font-weight: 700;">{{ number_format($summary['current_value'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a; font-weight: 800; background-color: #f8fafc;">
                        <td colspan="2">TOTAL VALUATION SUMMARY</td>
                        <td class="dep-print-num">{{ number_format($totalCost, 2) }}</td>
                        <td class="dep-print-num">{{ number_format($totalAccumulated, 2) }}</td>
                        <td class="dep-print-num">{{ number_format($totalCurrentValue, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Detailed Asset Depreciation Schedule -->
        <div>
            <div style="margin-bottom: 4px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #065f46; font-style: italic;">
                DETAILED ASSET DEPRECIATION SCHEDULE
            </div>
            <table class="dep-print-table">
                <colgroup>
                    <col style="width: 13%;">
                    <col style="width: 25%;">
                    <col style="width: 15%;">
                    <col style="width: 7%;">
                    <col style="width: 7%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>BAR CODE</th>
                        <th>ASSET NAME</th>
                        <th>CATEGORY</th>
                        <th class="dep-print-center">YEAR</th>
                        <th class="dep-print-center">LIFE</th>
                        <th class="dep-print-num">PURCHASE</th>
                        <th class="dep-print-num">ACCUMULATED</th>
                        <th class="dep-print-num">CURRENT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($allFilteredAssets ?? $assets) as $asset)
                        @php
                            $dep = $depreciationService->calculateForAsset($asset, $asOfDate);
                            $costVal = $asset->cost !== null ? (float) $asset->cost : null;
                        @endphp
                        <tr>
                            <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;">{{ $asset->asset_no }}</td>
                            <td>{{ $asset->asset_name }}</td>
                            <td>{{ $asset->category?->name ?? 'Uncategorized' }}</td>
                            <td class="dep-print-center">{{ $asset->date_acquired ? $asset->date_acquired->format('Y') : '-' }}</td>
                            <td class="dep-print-center">{{ $dep->isCalculable() ? $dep->getUsefulLifeYears() . 'y' : '-' }}</td>
                            <td class="dep-print-num">{{ $costVal !== null ? number_format($costVal, 2) : '-' }}</td>
                            <td class="dep-print-num">{{ $dep->isCalculable() ? number_format($dep->getAccumulatedDepreciation(), 2) : '0.00' }}</td>
                            <td class="dep-print-num" style="font-weight: 700;">
                                {{ $dep->isCalculable() ? number_format($dep->getCurrentValue(), 2) : ($costVal !== null ? number_format($costVal, 2) : '-') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr style="border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a; font-weight: 800; color: #065f46;">
                        <td colspan="5">Asset Count : {{ number_format(($allFilteredAssets ?? $assets)->count()) }} &nbsp;&nbsp;&nbsp; GRAND TOTAL</td>
                        <td class="dep-print-num">{{ number_format($totalCost, 2) }}</td>
                        <td class="dep-print-num">{{ number_format($totalAccumulated, 2) }}</td>
                        <td class="dep-print-num">{{ number_format($totalCurrentValue, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 14px; padding-top: 6px; border-top: 1px solid #0f172a; display: flex; justify-content: space-between; align-items: flex-start; font-size: 10.5px; color: #0f172a; line-height: 1.4;">
            <div>
                <div>Page No : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1</div>
                <div>Date Printed : {{ now()->format('n/j/Y g:i:s A') }}</div>
            </div>
            <div style="text-align: right;">
                <div>Prepared By : {{ auth()->user()?->name ?? auth()->user()?->username }}</div>
            </div>
        </div>
    </div>
@endsection
