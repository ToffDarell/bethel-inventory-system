@extends('layouts.app')

@section('content')
    <div class="space-y-6">
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
                        <span>Export CSV</span>
                    </a>
                    <a href="{{ route('reports.depreciation.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                       class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs shadow-2xs transition-colors">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Export Excel (.xlsx)</span>
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
                        <th class="py-3 px-3.5 text-right whitespace-nowrap" title="Expected value at the end of its useful life">Salvage</th>
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
                            {{ $asset->useful_life_years ? $asset->useful_life_years . ' yrs' : '-' }}
                        </td>
                        @if ($asset->cost !== null)
                            <td class="py-3 px-3.5 text-right text-xs text-slate-800 whitespace-nowrap tabular-nums">
                                ₱{{ number_format($asset->cost, 2) }}
                            </td>
                            <td class="py-3 px-3.5 text-right text-xs text-slate-700 whitespace-nowrap tabular-nums">
                                {{ $asset->salvage_value !== null ? '₱' . number_format($asset->salvage_value, 2) : '-' }}
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
                                {{ $asset->salvage_value !== null ? '₱' . number_format($asset->salvage_value, 2) : '-' }}
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
@endsection
