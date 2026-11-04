@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <x-page-header
            title="Depreciation Report"
            subtitle="Straight-line asset depreciation, accumulated loss, and current valuation across hospital inventory"
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

        <!-- Valuation Totals Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Acquisition Cost</span>
                <p class="text-2xl font-extrabold text-slate-900 font-mono mt-1">
                    ₱{{ number_format($totalCost, 2) }}
                </p>
                <span class="text-xs text-slate-400 mt-0.5 block">Original hospital investment</span>
            </div>

            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Accumulated Depreciation</span>
                <p class="text-2xl font-extrabold text-amber-700 font-mono mt-1">
                    ₱{{ number_format($totalAccumulated, 2) }}
                </p>
                <span class="text-xs text-slate-400 mt-0.5 block">Depreciated value elapsed to date</span>
            </div>

            <div class="p-5 rounded-xl bg-slate-900 text-white border border-slate-800 shadow-2xs">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Current Asset Value</span>
                <p class="text-2xl font-extrabold text-white font-mono mt-1">
                    ₱{{ number_format($totalCurrentValue, 2) }}
                </p>
                <span class="text-xs text-slate-400 mt-0.5 block">Current net book value</span>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <x-card padding="p-4">
            <form method="GET" action="{{ route('reports.depreciation') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <div class="sm:col-span-5">
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

                <div class="sm:col-span-5">
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

                <div class="sm:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 min-h-[44px] inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer">
                        Filter
                    </button>
                    @if ($selectedCategory || $selectedDepartment)
                        <a href="{{ route('reports.depreciation') }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- By Category Summary Table -->
        <x-card title="Category Valuation Summary" subtitle="Subtotals grouped by asset classification">
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
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ $summary['count'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-slate-800">
                            ₱{{ number_format($summary['cost'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-amber-800">
                            ₱{{ number_format($summary['accumulated'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs font-bold text-slate-900">
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
        <x-card title="Asset Depreciation Schedule" subtitle="Individual equipment straight-line calculations">
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="py-3 px-4 whitespace-nowrap">Asset No.</th>
                        <th class="py-3 px-4">Asset Name</th>
                        <th class="py-3 px-4 whitespace-nowrap">Category</th>
                        <th class="py-3 px-4 whitespace-nowrap">Date Acquired</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Cost</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Accumulated</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Current Value</th>
                    </tr>
                </x-slot:header>

                @forelse ($assets as $asset)
                    @php
                        $dep = $depreciationService->calculateForAsset($asset);
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono text-xs font-semibold text-slate-900 whitespace-nowrap">
                            <a href="{{ route('assets.show', $asset) }}" class="hover:text-primary hover:underline">
                                {{ $asset->asset_no }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-900">
                            {{ $asset->asset_name }}
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-700 whitespace-nowrap">
                            {{ $asset->category?->name ?? 'Uncategorized' }}
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-700 whitespace-nowrap">
                            {{ $asset->date_acquired ? $asset->date_acquired->format('M d, Y') : 'Not recorded' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-slate-800 whitespace-nowrap">
                            {{ $asset->cost !== null ? '₱' . number_format($asset->cost, 2) : 'No cost entered' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs text-amber-800 whitespace-nowrap">
                            {{ $dep->isCalculable() ? '₱' . number_format($dep->getAccumulatedDepreciation(), 2) : 'Not enough data' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                            {{ $dep->isCalculable() ? '₱' . number_format($dep->getCurrentValue(), 2) : ($asset->cost !== null ? '₱' . number_format($asset->cost, 2) : 'No cost entered') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">No assets found matching the selected filters.</td>
                    </tr>
                @endforelse

                <!-- Table Totals Row -->
                <x-slot:footer>
                    <tr class="bg-slate-100/80 font-semibold border-t-2 border-slate-300">
                        <td colspan="4" class="py-3.5 px-4 text-slate-900 uppercase tracking-wider text-xs font-bold">
                            Totals
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-slate-900">
                            ₱{{ number_format($totalCost, 2) }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-amber-900">
                            ₱{{ number_format($totalAccumulated, 2) }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-xs font-bold text-primary">
                            ₱{{ number_format($totalCurrentValue, 2) }}
                        </td>
                    </tr>
                </x-slot:footer>
            </x-table>

            <div class="mt-4">
                {{ $assets->links() }}
            </div>
        </x-card>
    </div>
@endsection
