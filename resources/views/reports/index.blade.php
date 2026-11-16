@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-page-header
            title="Reports & Excel Import / Export"
            subtitle="View equipment values, depreciation loss, and download or upload Excel files"
        >
            @if ($isAdmin)
                <x-slot:actions>
                    <x-button variant="primary" :href="route('reports.depreciation')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>View Depreciation Report</span>
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-950" role="alert" tabindex="-1">
                <p class="text-sm font-semibold text-red-900 mb-1">Please review the import form</p>
                <ul class="list-disc list-inside text-xs text-red-800 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Printable Inventory Reports Hub (5 Reports matching Bethel Baptist Hospital standard sheets) -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-2xs space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Printable Inventory Reports</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Open a grouped hospital report sheet with live filters and a Print Report button
                    </p>
                </div>
                <span class="text-xs font-semibold text-slate-500 tabular-nums">
                    {{ number_format($totalAssets) }} total {{ \Illuminate\Support\Str::plural('asset', $totalAssets) }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $isAdmin ? '5' : '4' }} gap-3">
                <!-- 1. Asset by Category -->
                <a href="{{ route('reports.print', ['type' => 'category']) }}"
                   class="group p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-primary hover:shadow-xs transition-all flex flex-col justify-between gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-900 group-hover:text-primary">Asset by Category</span>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/>
                        </svg>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-snug">Grouped by category and department sub-totals</p>
                </a>

                <!-- 2. Asset by Date Acquired -->
                <a href="{{ route('reports.print', ['type' => 'date_acquired']) }}"
                   class="group p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-primary hover:shadow-xs transition-all flex flex-col justify-between gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-900 group-hover:text-primary">Asset by Date Acquired</span>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-snug">Grouped by acquisition year and department</p>
                </a>

                <!-- 3. Asset by Department -->
                <a href="{{ route('reports.print', ['type' => 'department']) }}"
                   class="group p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-primary hover:shadow-xs transition-all flex flex-col justify-between gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-900 group-hover:text-primary">Asset by Department</span>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-snug">Room and department inventory sign-off sheets</p>
                </a>

                <!-- 4. Asset by Vendor -->
                <a href="{{ route('reports.print', ['type' => 'vendor']) }}"
                   class="group p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-primary hover:shadow-xs transition-all flex flex-col justify-between gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-900 group-hover:text-primary">Asset by Vendor</span>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                        </svg>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-snug">Grouped by supplier or donation source</p>
                </a>

                <!-- 5. Depreciation Summary (Admin only) -->
                @if ($isAdmin)
                    <a href="{{ route('reports.depreciation') }}"
                       class="group p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-primary hover:shadow-xs transition-all flex flex-col justify-between gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-900 group-hover:text-primary">Depreciation Summary</span>
                            <svg class="w-4 h-4 text-emerald-600 group-hover:text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-snug">Valuation, accumulated loss, and net book value</p>
                    </a>
                @endif
            </div>
        </div>

        @if ($isAdmin)
            <!-- Role-Aware Report Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-2">
                <button type="button"
                        id="btn-report-financial"
                        onclick="switchReportTab('financial')"
                        class="report-tab-btn min-h-[42px] px-4 py-2.5 text-xs font-semibold rounded-t-lg border-b-2 border-primary text-primary bg-white shadow-2xs transition-colors flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Financial Reports &amp; Valuation</span>
                </button>

                <button type="button"
                        id="btn-report-import-export"
                        onclick="switchReportTab('import-export')"
                        class="report-tab-btn min-h-[42px] px-4 py-2.5 text-xs font-semibold rounded-t-lg border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                    <span>Excel Import &amp; Export</span>
                </button>
            </div>
        @endif

        @if ($isAdmin)
            <!-- TAB 1: FINANCIAL REPORTS & VALUATION (ADMIN ONLY) -->
            <div id="panel-report-financial" class="report-tab-panel space-y-6">
                <!-- Compact Overview Card -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-900 text-white uppercase tracking-wider">
                                    Admin Only
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                    Estimated values for tracking, not an official accounting record
                                </span>
                                <h2 class="text-lg font-bold text-slate-900 tracking-tight">
                                    Depreciation &amp; Asset Valuation
                                </h2>
                            </div>
                            <p class="text-xs text-slate-500">
                                See what the hospital paid, value lost over time, and what equipment is worth today
                            </p>
                        </div>

                        <div>
                            <a href="{{ route('reports.depreciation') }}"
                               class="min-h-[42px] inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-2xs transition-colors">
                                <span>Open full report</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>

                    <!-- Valuation Totals: Three Totals in One Row -->
                    <div class="p-6 bg-slate-50/50 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Acquisition Cost</span>
                                <p class="text-2xl font-extrabold text-slate-900 tabular-nums mt-1">
                                    ₱{{ number_format($totalCost, 2) }}
                                </p>
                                <span class="text-xs text-slate-500 mt-0.5 block">What the hospital paid</span>
                            </div>

                            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Accumulated Depreciation</span>
                                <p class="text-2xl font-extrabold text-amber-700 tabular-nums mt-1">
                                    ₱{{ number_format($totalAccumulated, 2) }}
                                </p>
                                <span class="text-xs text-slate-500 mt-0.5 block">Value lost so far</span>
                            </div>

                            <div class="p-5 rounded-xl bg-slate-900 text-white border border-slate-800 shadow-2xs">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Net Book Value</span>
                                <p class="text-2xl font-extrabold text-white tabular-nums mt-1">
                                    ₱{{ number_format($totalCurrentValue, 2) }}
                                </p>
                                <span class="text-xs text-slate-400 mt-0.5 block">What all equipment is worth today</span>
                            </div>
                        </div>

                        <!-- Method Note Banner -->
                        <div class="p-3 bg-white border border-slate-200 rounded-lg text-xs text-slate-600 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>
                                    <strong>Method:</strong> Straight-line &bull; 
                                    <strong>As-of Date:</strong> {{ now()->format('F j, Y') }} &bull; 
                                    <strong>Assumed Start Date:</strong> {{ $policyConventionLabel ?? 'Counted from January 1 of the Year Bought' }}
                                    @if (($policyMinCost ?? 0) > 0)
                                        &bull; <strong>Minimum Cost:</strong> ₱{{ number_format($policyMinCost, 2) }}
                                    @endif
                                </span>
                            </div>
                            <span class="text-slate-400">Calculated from {{ $activeDepreciableCount }} active {{ \Illuminate\Support\Str::plural('asset', $activeDepreciableCount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 2: EXCEL IMPORT & EXPORT -->
        <div id="panel-report-import-export" class="report-tab-panel space-y-6 {{ $isAdmin ? 'hidden' : '' }}">
            <!-- 4-Step Import Stepper -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 font-semibold flex items-center justify-center shrink-0">1</span>
                        <div>
                            <p class="font-medium text-slate-900">Download Template</p>
                            <p class="text-[11px] text-slate-500">Get standard spreadsheet</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-sky-50/70 border border-sky-200">
                        <span class="w-6 h-6 rounded-full bg-primary text-white font-semibold flex items-center justify-center shrink-0">2</span>
                        <div>
                            <p class="font-semibold text-slate-900">Upload File</p>
                            <p class="text-[11px] text-slate-600">Select .xlsx or .csv</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white border border-slate-100 opacity-60">
                        <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 font-semibold flex items-center justify-center shrink-0">3</span>
                        <div>
                            <p class="font-medium text-slate-700">Review Preview</p>
                            <p class="text-[11px] text-slate-400">Verify rows and duplicates</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white border border-slate-100 opacity-60">
                        <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 font-semibold flex items-center justify-center shrink-0">4</span>
                        <div>
                            <p class="font-medium text-slate-700">Confirm &amp; Import</p>
                            <p class="text-[11px] text-slate-400">Save to inventory records</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Export Assets to Excel -->
                <x-card title="Export Assets to Excel" subtitle="Download hospital equipment records as an Excel spreadsheet">
                    <p class="text-sm text-slate-600 mb-5">
                        Download a spreadsheet of all registered hospital equipment ({{ $totalAssets }} total {{ \Illuminate\Support\Str::plural('asset', $totalAssets) }}). Filter by department or status if needed.
                    </p>

                    <form method="GET" action="{{ route('reports.export') }}" class="space-y-4">
                        <div>
                            <label for="export_department" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Department Filter
                            </label>
                            <select id="export_department"
                                    name="department"
                                    class="w-full min-h-[44px] px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                                <option value="">All departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="export_status" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Status Filter
                            </label>
                            <select id="export_status"
                                    name="status"
                                    class="w-full min-h-[44px] px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-2 flex flex-col sm:flex-row gap-3">
                            <button type="submit"
                                    name="format"
                                    value="csv"
                                    class="flex-1 min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary hover:bg-slate-800 text-white font-medium text-sm shadow-2xs transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>Raw data (CSV)</span>
                            </button>
                            <button type="submit"
                                    name="format"
                                    value="xlsx"
                                    class="flex-1 min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-medium text-sm shadow-2xs transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>Export to Excel (.xlsx)</span>
                            </button>
                            <button type="submit"
                                    formaction="{{ route('reports.print') }}"
                                    name="type"
                                    value="department"
                                    class="flex-1 min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-medium text-sm shadow-2xs transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/>
                                </svg>
                                <span>Print Report</span>
                            </button>
                        </div>
                    </form>
                </x-card>

                <!-- Import Assets from Excel -->
                <x-card title="Import Assets from Excel" subtitle="Add multiple equipment items at once using an Excel or CSV file">
                    <p class="text-sm text-slate-600 mb-4">
                        Upload an Excel (<code>.xlsx</code>, <code>.xls</code>) or CSV (<code>.csv</code>) file. You can check all rows and duplicates before anything is saved.
                    </p>

                    <!-- Step 1 Template Download Box -->
                    <div class="mb-5 p-3.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-slate-900">Need the template format?</p>
                            <p class="text-[11px] text-slate-500">Download the standard column layout</p>
                        </div>
                        <a href="{{ route('reports.template') }}"
                           class="min-h-[38px] inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors shrink-0">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Download Template</span>
                        </a>
                    </div>

                    <form method="POST"
                          action="{{ route('reports.import.preview') }}"
                          enctype="multipart/form-data"
                          class="space-y-4">
                        @csrf

                        <div>
                            <label for="import_file" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Spreadsheet File <span class="text-brand-red font-semibold">*</span>
                            </label>
                            <input id="import_file"
                                   name="file"
                                   type="file"
                                   required
                                   accept=".xlsx,.xls,.csv"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 focus:outline-none focus:border-primary transition-colors">
                            <p class="text-xs text-slate-500 mt-1">
                                <code>date_acquired</code> and <code>serial_no</code> are optional. Blank <code>asset_no</code> rows auto-generate sequential codes.
                            </p>
                        </div>

                        <div class="pt-2">
                            <x-button variant="primary" type="submit" class="w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span>Preview Import Rows</span>
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </div>

    @if ($isAdmin)
        <script>
            function switchReportTab(tabId) {
                document.querySelectorAll('.report-tab-btn').forEach(btn => {
                    btn.classList.remove('border-primary', 'text-primary', 'bg-white', 'shadow-2xs');
                    btn.classList.add('border-transparent', 'text-slate-500', 'hover:text-slate-700', 'hover:border-slate-300');
                });
                const activeBtn = document.getElementById('btn-report-' + tabId);
                if (activeBtn) {
                    activeBtn.classList.remove('border-transparent', 'text-slate-500', 'hover:text-slate-700', 'hover:border-slate-300');
                    activeBtn.classList.add('border-primary', 'text-primary', 'bg-white', 'shadow-2xs');
                }
                document.querySelectorAll('.report-tab-panel').forEach(panel => {
                    panel.classList.add('hidden');
                });
                const activePanel = document.getElementById('panel-report-' + tabId);
                if (activePanel) {
                    activePanel.classList.remove('hidden');
                }
                try {
                    localStorage.setItem('bethel_report_active_tab', tabId);
                } catch (e) {}
            }

            document.addEventListener('DOMContentLoaded', function() {
                @if ($errors->any() || session('error'))
                    switchReportTab('import-export');
                @else
                    var savedTab = 'financial';
                    try {
                        var stored = localStorage.getItem('bethel_report_active_tab');
                        if (stored === 'import-export' || stored === 'financial') {
                            savedTab = stored;
                        }
                    } catch (e) {}
                    if (window.location.hash === '#import-export') {
                        savedTab = 'import-export';
                    } else if (window.location.hash === '#financial') {
                        savedTab = 'financial';
                    }
                    switchReportTab(savedTab);
                @endif
            });
        </script>
    @endif
@endsection
