@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <x-page-header
            title="Scan Property Tag"
            subtitle="Quick barcode and QR tag lookup for equipment on hospital floors"
        />

        <!-- Scan / Audit Mode Switcher -->
        <div class="flex items-center justify-between gap-3 p-1.5 rounded-xl bg-slate-100 border border-slate-200">
            <button type="button"
                    id="mode-quick-btn"
                    onclick="switchScanMode('quick')"
                    class="flex-1 min-h-[40px] px-4 py-2 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-2xs transition-all flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-[#0f5d8c]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Single Tag Scan</span>
            </button>
            <button type="button"
                    id="mode-audit-btn"
                    onclick="switchScanMode('audit')"
                    class="flex-1 min-h-[40px] px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span>Location Audit Mode</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- MODE 1: SINGLE TAG SCAN (Default Mode)     -->
        <!-- ========================================== -->
        <div id="quick-scan-section" class="space-y-6">
            <!-- Server-side Not Found State -->
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-amber-50/90 border border-amber-300 text-amber-950 shadow-2xs" role="alert" tabindex="-1">
                    <div class="flex items-start gap-3.5">
                        <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-sm font-semibold text-amber-950">
                                {{ $errors->first('q') }}
                            </h2>
                            @if (! empty($notFoundCode))
                                <p class="text-xs text-amber-800 mt-1">
                                    Tag code <span class="font-mono font-medium text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded">{{ $notFoundCode }}</span> is not registered in the inventory.
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-2.5">
                                    <x-button variant="primary" :href="route('assets.create', ['asset_no' => $notFoundCode])">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>Register New Asset with "{{ $notFoundCode }}"</span>
                                    </x-button>
                                    <x-button variant="secondary" :href="route('assets.index', ['search' => $notFoundCode])">
                                        <span>Search All Records</span>
                                    </x-button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Client-side Inline Not Found State -->
            <div id="inline-not-found" class="hidden p-4 rounded-xl bg-amber-50/90 border border-amber-300 text-amber-950 shadow-2xs" role="alert">
                <div class="flex items-start gap-3.5">
                    <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-sm font-semibold text-amber-950">
                            No asset found with code "<span id="not-found-code-text"></span>"
                        </h2>
                        <p class="text-xs text-amber-800 mt-1">
                            Please check the scanned tag or register this equipment into the hospital database.
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <a id="register-new-btn" href="#" class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a70] text-white text-xs font-medium shadow-2xs transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Register as New Asset</span>
                            </a>
                            <button type="button" onclick="document.getElementById('inline-not-found').classList.add('hidden'); document.getElementById('scan-input').focus();" class="min-h-[44px] px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Scanner Card (Smaller Hero) -->
            <x-card padding="p-5 sm:p-6">
                <form id="scan-form" method="GET" action="{{ route('scan.lookup') }}" class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <label for="scan-input" class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#0f5d8c]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <span>Tag Code or Asset No.</span>
                        </label>

                        <div class="flex items-center gap-1.5 text-xs text-slate-600">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Scanner ready</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-2.5">
                        <div class="relative w-full flex-1">
                            <input id="scan-input"
                                   name="q"
                                   type="text"
                                   autofocus
                                   autocomplete="off"
                                   value="{{ old('q', $notFoundCode ?? '') }}"
                                   placeholder="Scan QR tag or type code (e.g. BBH-000001)"
                                   class="w-full min-h-[46px] px-3.5 py-2 text-base font-mono font-medium text-slate-900 bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                        </div>

                        <button type="submit"
                                id="scan-submit-btn"
                                class="w-full sm:w-auto min-h-[46px] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a70] text-white font-medium text-sm shadow-2xs transition-colors cursor-pointer shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Find Asset</span>
                        </button>
                    </div>

                    <p class="text-xs text-slate-600 pt-1">
                        USB handheld scanners will enter the property code and find the asset automatically.
                    </p>
                </form>
            </x-card>

            <!-- Inline Scanned Asset Preview Panel -->
            <div id="asset-preview-panel" class="hidden transition-all duration-200">
                <x-card padding="p-5 sm:p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Tag Scanned
                                </span>
                                <span id="preview-timestamp" class="text-xs text-slate-600 tabular-nums"></span>
                            </div>
                            <h2 id="preview-asset-name" class="text-xl font-bold text-slate-900 mt-1"></h2>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs">
                                <span id="preview-asset-no" class="font-mono font-medium text-slate-800 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded"></span>
                                <span class="text-slate-300">&bull;</span>
                                <span id="preview-status-badge"></span>
                            </div>
                        </div>

                        <!-- Quick Actions for Scanned Asset -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a id="preview-move-btn" href="#" class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 shadow-2xs transition-colors">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span>Move Asset</span>
                            </a>
                            <a id="preview-print-btn" href="#" class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-800 shadow-2xs transition-colors">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>Print Tag</span>
                            </a>
                            <a id="preview-view-btn" href="#" class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a70] text-white text-xs font-medium shadow-2xs transition-colors">
                                <span>View Details &rarr;</span>
                            </a>
                        </div>
                    </div>

                    <!-- Quick Location / Department Metadata -->
                    <div class="pt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="flex items-center gap-2.5 p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <div class="w-7 h-7 rounded bg-white border border-slate-200 flex items-center justify-center shrink-0 text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-slate-600 block text-[11px]">Current Location</span>
                                <strong id="preview-location" class="font-medium text-slate-900 truncate block text-sm"></strong>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <div class="w-7 h-7 rounded bg-white border border-slate-200 flex items-center justify-center shrink-0 text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-slate-600 block text-[11px]">Department Unit</span>
                                <strong id="preview-department" class="font-medium text-slate-900 truncate block text-sm"></strong>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Recent Scans Section -->
            <x-card padding="p-0">
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Recent Scans</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Quick access to tags scanned during this session</p>
                        </div>
                        <button type="button"
                                id="clear-recent-scans-btn"
                                class="text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors hidden cursor-pointer min-h-[32px] px-2 py-1">
                            Clear list
                        </button>
                    </div>
                </x-slot:header>

                <div id="recent-scans-container">
                    <div id="no-recent-scans" class="p-8 text-center text-slate-500 text-sm">
                        No tags scanned in this browser session yet. Scanned property tags will appear here for fast re-access.
                    </div>
                    <ul id="recent-scans-list" class="divide-y divide-slate-100 hidden">
                        <!-- Populated via JavaScript -->
                    </ul>
                </div>
            </x-card>
        </div>

        <!-- ========================================== -->
        <!-- MODE 2: LOCATION AUDIT MODE (Phase F)      -->
        <!-- ========================================== -->
        <div id="audit-mode-section" class="hidden space-y-6">
            <!-- Setup Card: Select Location -->
            <x-card padding="p-5 sm:p-6" id="audit-setup-card">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Location Inventory Audit</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Select a hospital room or ward to perform a physical inventory audit sweep.
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-sky-50 text-[#0f5d8c] border border-sky-200 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#0f5d8c]"></span>
                            <span>Session Based</span>
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                        <div class="relative w-full flex-1">
                            <label for="audit-location-select" class="sr-only">Select Ward or Room to Audit</label>
                            <select id="audit-location-select"
                                    class="w-full min-h-[46px] px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                                <option value="">-- Choose a Hospital Location to Audit --</option>
                            </select>
                        </div>

                        <button type="button"
                                id="start-audit-btn"
                                onclick="startLocationAudit()"
                                class="w-full sm:w-auto min-h-[46px] px-6 py-2.5 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a70] text-white font-medium text-sm shadow-2xs transition-colors cursor-pointer shrink-0 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span>Start Room Audit</span>
                        </button>
                    </div>
                </div>
            </x-card>

            <!-- Active Audit Dashboard (Shown after location is selected) -->
            <div id="active-audit-dashboard" class="hidden space-y-6">
                <!-- Location Header & Audit Progress Bar -->
                <x-card padding="p-5 sm:p-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Active Audit Location</span>
                                <h2 id="active-location-title" class="text-xl font-bold text-slate-900 mt-0.5"></h2>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        onclick="exportAuditToExcel()"
                                        class="min-h-[40px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-800 shadow-2xs transition-colors cursor-pointer">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>Export to Excel (.csv)</span>
                                </button>
                                <button type="button"
                                        onclick="resetLocationAudit()"
                                        class="min-h-[40px] px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors cursor-pointer">
                                    Change Location
                                </button>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-600 mb-1.5">
                                <span id="audit-progress-label" class="font-medium">Found 0 of 0 items (0%)</span>
                                <span id="audit-status-badge" class="font-semibold text-amber-700">Audit in progress</span>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                <div id="audit-progress-bar" class="h-full bg-emerald-600 rounded-full transition-all duration-300" style="width: 0%;"></div>
                            </div>
                        </div>

                        <!-- 4 Summary Metric Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Expected</span>
                                <p id="metric-expected" class="text-2xl font-bold text-slate-900 tabular-nums mt-1">0</p>
                                <span class="text-[11px] text-slate-500">Registered here</span>
                            </div>

                            <div class="p-3 rounded-lg bg-emerald-50/70 border border-emerald-200">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-800">Found</span>
                                <p id="metric-found" class="text-2xl font-bold text-emerald-950 tabular-nums mt-1">0</p>
                                <span class="text-[11px] text-emerald-700">Verified present</span>
                            </div>

                            <div class="p-3 rounded-lg bg-amber-50/70 border border-amber-200">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-800">Missing</span>
                                <p id="metric-missing" class="text-2xl font-bold text-amber-950 tabular-nums mt-1">0</p>
                                <span class="text-[11px] text-amber-700">Not yet scanned</span>
                            </div>

                            <div class="p-3 rounded-lg bg-sky-50/70 border border-sky-200">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#0f5d8c]">Unexpected</span>
                                <p id="metric-unexpected" class="text-2xl font-bold text-slate-900 tabular-nums mt-1">0</p>
                                <span class="text-[11px] text-slate-600">From other rooms</span>
                            </div>
                        </div>
                    </div>
                </x-card>

                <!-- Audit Live Scanner Input -->
                <x-card padding="p-5">
                    <form id="audit-scan-form" onsubmit="handleAuditScan(event)" class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <label for="audit-scan-input" class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#0f5d8c]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                                <span>Scan Item into Current Location</span>
                            </label>

                            <div class="flex items-center gap-1.5 text-xs text-slate-600">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Audit active</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-2.5">
                            <div class="relative w-full flex-1">
                                <input id="audit-scan-input"
                                       type="text"
                                       autocomplete="off"
                                       placeholder="Scan QR tag or type Asset No. (e.g. BBH-000001)"
                                       class="w-full min-h-[46px] px-3.5 py-2 text-base font-mono font-medium text-slate-900 bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                            </div>

                            <button type="submit"
                                    class="w-full sm:w-auto min-h-[46px] inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a70] text-white font-medium text-sm shadow-2xs transition-colors cursor-pointer shrink-0">
                                <span>Verify Item</span>
                            </button>
                        </div>

                        <!-- Feedback Banner for Last Scan -->
                        <div id="audit-feedback" class="hidden p-3 rounded-lg text-xs font-medium flex items-center justify-between">
                            <span id="audit-feedback-text"></span>
                            <span id="audit-feedback-time" class="text-[11px] opacity-75 tabular-nums"></span>
                        </div>
                    </form>
                </x-card>

                <!-- Audit Items Checklist & Table -->
                <x-card padding="p-0">
                    <x-slot:header>
                        <div class="flex flex-wrap items-center justify-between gap-3 w-full">
                            <!-- Filter Tabs -->
                            <div class="flex items-center gap-1.5 text-xs">
                                <button type="button"
                                        onclick="setAuditFilter('all')"
                                        id="filter-btn-all"
                                        class="audit-filter-btn min-h-[32px] px-3 py-1 rounded-md font-semibold bg-slate-200 text-slate-900 transition-colors">
                                    All (<span id="count-all">0</span>)
                                </button>
                                <button type="button"
                                        onclick="setAuditFilter('found')"
                                        id="filter-btn-found"
                                        class="audit-filter-btn min-h-[32px] px-3 py-1 rounded-md font-medium text-slate-600 hover:bg-slate-100 transition-colors">
                                    Found (<span id="count-found">0</span>)
                                </button>
                                <button type="button"
                                        onclick="setAuditFilter('missing')"
                                        id="filter-btn-missing"
                                        class="audit-filter-btn min-h-[32px] px-3 py-1 rounded-md font-medium text-slate-600 hover:bg-slate-100 transition-colors">
                                    Missing (<span id="count-missing">0</span>)
                                </button>
                                <button type="button"
                                        onclick="setAuditFilter('unexpected')"
                                        id="filter-btn-unexpected"
                                        class="audit-filter-btn min-h-[32px] px-3 py-1 rounded-md font-medium text-slate-600 hover:bg-slate-100 transition-colors">
                                    Unexpected (<span id="count-unexpected">0</span>)
                                </button>
                            </div>

                            <button type="button"
                                    onclick="exportAuditToExcel()"
                                    class="min-h-[36px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>Export Audit (.csv)</span>
                            </button>
                        </div>
                    </x-slot:header>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-[15px]">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                                <tr>
                                    <th class="py-3 px-4">Audit Result</th>
                                    <th class="py-3 px-4">Asset No.</th>
                                    <th class="py-3 px-4">Equipment Name</th>
                                    <th class="py-3 px-4">Registered Location</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="audit-items-tbody" class="divide-y divide-slate-100">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <!-- Script for Single Tag Scan and Location Audit Mode -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mode Switcher Elements
            const modeQuickBtn = document.getElementById('mode-quick-btn');
            const modeAuditBtn = document.getElementById('mode-audit-btn');
            const quickScanSection = document.getElementById('quick-scan-section');
            const auditModeSection = document.getElementById('audit-mode-section');

            // Quick Scan Elements
            const input = document.getElementById('scan-input');
            const form = document.getElementById('scan-form');
            const list = document.getElementById('recent-scans-list');
            const emptyNotice = document.getElementById('no-recent-scans');
            const clearBtn = document.getElementById('clear-recent-scans-btn');
            const previewPanel = document.getElementById('asset-preview-panel');
            const inlineNotFound = document.getElementById('inline-not-found');
            const STORAGE_KEY = 'bbh_recent_scans_history';

            // Location Audit Elements
            const auditLocationSelect = document.getElementById('audit-location-select');
            const auditSetupCard = document.getElementById('audit-setup-card');
            const activeAuditDashboard = document.getElementById('active-audit-dashboard');
            const activeLocationTitle = document.getElementById('active-location-title');
            const auditScanInput = document.getElementById('audit-scan-input');
            const auditFeedback = document.getElementById('audit-feedback');
            const auditFeedbackText = document.getElementById('audit-feedback-text');
            const auditFeedbackTime = document.getElementById('audit-feedback-time');
            const auditTbody = document.getElementById('audit-items-tbody');

            // Mode switching
            window.switchScanMode = function (mode) {
                if (mode === 'audit') {
                    quickScanSection.classList.add('hidden');
                    auditModeSection.classList.remove('hidden');

                    modeQuickBtn.classList.remove('bg-white', 'text-slate-900', 'font-semibold', 'shadow-2xs');
                    modeQuickBtn.classList.add('text-slate-600', 'font-medium');
                    modeQuickBtn.querySelector('svg').classList.remove('text-[#0f5d8c]');
                    modeQuickBtn.querySelector('svg').classList.add('text-slate-400');

                    modeAuditBtn.classList.add('bg-white', 'text-slate-900', 'font-semibold', 'shadow-2xs');
                    modeAuditBtn.classList.remove('text-slate-600', 'font-medium');
                    modeAuditBtn.querySelector('svg').classList.add('text-[#0f5d8c]');
                    modeAuditBtn.querySelector('svg').classList.remove('text-slate-400');

                    loadAuditLocations();
                    if (auditScanInput && !activeAuditDashboard.classList.contains('hidden')) {
                        auditScanInput.focus();
                    }
                } else {
                    auditModeSection.classList.add('hidden');
                    quickScanSection.classList.remove('hidden');

                    modeAuditBtn.classList.remove('bg-white', 'text-slate-900', 'font-semibold', 'shadow-2xs');
                    modeAuditBtn.classList.add('text-slate-600', 'font-medium');
                    modeAuditBtn.querySelector('svg').classList.remove('text-[#0f5d8c]');
                    modeAuditBtn.querySelector('svg').classList.add('text-slate-400');

                    modeQuickBtn.classList.add('bg-white', 'text-slate-900', 'font-semibold', 'shadow-2xs');
                    modeQuickBtn.classList.remove('text-slate-600', 'font-medium');
                    modeQuickBtn.querySelector('svg').classList.add('text-[#0f5d8c]');
                    modeQuickBtn.querySelector('svg').classList.remove('text-slate-400');

                    if (input) {
                        input.focus();
                        input.select();
                    }
                }
            };

            // Quick Scan Logic
            if (input) {
                input.focus();
                input.select();
            }

            function loadRecentScans() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    return raw ? JSON.parse(raw) : [];
                } catch (e) {
                    return [];
                }
            }

            function saveRecentScan(asset) {
                if (!asset || !asset.asset_no) return;
                const clean = asset.asset_no.trim().toUpperCase();
                let scans = loadRecentScans().filter(s => s.code !== clean);
                scans.unshift({
                    id: asset.id,
                    code: clean,
                    name: asset.asset_name || clean,
                    location: asset.location || 'Unassigned',
                    department: asset.department || '',
                    status: asset.status || 'In Use',
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });
                if (scans.length > 8) scans = scans.slice(0, 8);
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(scans));
                } catch (e) {}
            }

            function getStatusBadgeHtml(status) {
                const s = status || 'In Use';
                if (s === 'In Use') {
                    return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> In Use
                    </span>`;
                }
                if (s === 'Under Repair') {
                    return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Under Repair
                    </span>`;
                }
                if (s === 'Stored') {
                    return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-700 border border-slate-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Stored
                    </span>`;
                }
                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> ${s}
                </span>`;
            }

            function displayAssetPreview(asset) {
                if (!asset || !previewPanel) return;

                if (inlineNotFound) inlineNotFound.classList.add('hidden');

                document.getElementById('preview-asset-name').textContent = asset.asset_name;
                document.getElementById('preview-asset-no').textContent = asset.asset_no;
                document.getElementById('preview-timestamp').textContent = 'Scanned just now (' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ')';
                document.getElementById('preview-status-badge').innerHTML = getStatusBadgeHtml(asset.status);
                document.getElementById('preview-location').textContent = asset.location || 'Unassigned';
                document.getElementById('preview-department').textContent = asset.department || 'Not specified';

                document.getElementById('preview-view-btn').href = '/assets/' + asset.id;
                document.getElementById('preview-move-btn').href = '/assets/' + asset.id + '/move';
                document.getElementById('preview-print-btn').href = '/tags/print?ids[]=' + asset.id;

                previewPanel.classList.remove('hidden');

                saveRecentScan(asset);
                renderRecentScans();

                if (input) {
                    input.value = '';
                    input.focus();
                }
            }

            function renderRecentScans() {
                const scans = loadRecentScans();
                if (!list || !emptyNotice) return;

                if (scans.length === 0) {
                    list.classList.add('hidden');
                    emptyNotice.classList.remove('hidden');
                    if (clearBtn) clearBtn.classList.add('hidden');
                    return;
                }

                emptyNotice.classList.add('hidden');
                list.classList.remove('hidden');
                if (clearBtn) clearBtn.classList.remove('hidden');

                list.innerHTML = scans.map(s => {
                    const viewUrl = s.id ? '/assets/' + s.id : ('{{ route('scan.lookup') }}?q=' + encodeURIComponent(s.code));
                    const moveUrl = s.id ? '/assets/' + s.id + '/move' : '#';
                    const printUrl = s.id ? '/tags/print?ids[]=' + s.id : '#';

                    return `
                        <li class="px-5 sm:px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-mono text-xs font-medium shrink-0">
                                    ${s.code}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">${s.name || s.code}</p>
                                    <p class="text-xs text-slate-600 flex items-center gap-2 mt-0.5">
                                        <span>${s.location || 'Hospital Floor'}</span>
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="tabular-nums">Scanned at ${s.time}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                ${s.id ? `
                                    <a href="${moveUrl}"
                                       class="min-h-[40px] inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
                                        Move
                                    </a>
                                    <a href="${printUrl}"
                                       class="min-h-[40px] inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
                                        Print Tag
                                    </a>
                                ` : ''}
                                <a href="${viewUrl}"
                                   class="min-h-[40px] inline-flex items-center px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-800 transition-colors">
                                    Open Asset &rarr;
                                </a>
                            </div>
                        </li>
                    `;
                }).join('');
            }

            async function performInlineLookup(query) {
                const clean = (query || '').trim();
                if (!clean) return false;

                try {
                    const res = await fetch('/api/assets/quick-search?q=' + encodeURIComponent(clean));
                    if (!res.ok) return false;
                    const assets = await res.json();

                    if (Array.isArray(assets) && assets.length > 0) {
                        const exact = assets.find(a => a.asset_no.toLowerCase() === clean.toLowerCase());
                        const match = exact || (assets.length === 1 ? assets[0] : null);

                        if (match) {
                            displayAssetPreview(match);
                            return true;
                        }
                    }
                } catch (e) {}

                return false;
            }

            if (form && input) {
                form.addEventListener('submit', async function (e) {
                    const val = (input.value || '').trim();
                    if (!val) return;

                    e.preventDefault();
                    const found = await performInlineLookup(val);
                    if (!found) {
                        form.submit();
                    }
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    try {
                        localStorage.removeItem(STORAGE_KEY);
                    } catch (e) {}
                    renderRecentScans();
                });
            }

            renderRecentScans();

            // ==========================================
            // LOCATION AUDIT LOGIC (Phase F)
            // ==========================================
            let auditState = {
                location: '',
                expected: [],    // Assets registered to this location: { id, asset_no, asset_name, department, location, status, found: bool, scan_time: null }
                unexpected: [],  // Assets found here but registered elsewhere: { id, asset_no, asset_name, department, location, scan_time: string }
                currentFilter: 'all'
            };

            const AUDIT_STORAGE_KEY = 'bbh_active_location_audit';

            async function loadAuditLocations() {
                if (auditLocationSelect.children.length > 1) return;
                try {
                    const res = await fetch('/api/audit/locations');
                    if (!res.ok) return;
                    const locs = await res.json();
                    if (Array.isArray(locs)) {
                        locs.forEach(function (loc) {
                            const opt = document.createElement('option');
                            opt.value = loc;
                            opt.textContent = loc;
                            auditLocationSelect.appendChild(opt);
                        });
                    }
                } catch (e) {}
            }

            window.startLocationAudit = async function () {
                const loc = auditLocationSelect.value.trim();
                if (!loc) {
                    alert('Please select a hospital location to audit.');
                    return;
                }

                try {
                    const res = await fetch('/api/audit/location-assets?location=' + encodeURIComponent(loc));
                    if (!res.ok) return;
                    const assets = await res.json();

                    auditState = {
                        location: loc,
                        expected: assets.map(a => Object.assign({}, a, { found: false, scan_time: null })),
                        unexpected: [],
                        currentFilter: 'all'
                    };

                    saveAuditSession();
                    renderAuditDashboard();
                } catch (e) {
                    alert('Could not load assets for ' + loc);
                }
            };

            window.resetLocationAudit = function () {
                if (confirm('Are you sure you want to exit or reset the current location audit session?')) {
                    sessionStorage.removeItem(AUDIT_STORAGE_KEY);
                    auditState = { location: '', expected: [], unexpected: [], currentFilter: 'all' };
                    activeAuditDashboard.classList.add('hidden');
                    auditSetupCard.classList.remove('hidden');
                    auditLocationSelect.focus();
                }
            };

            function saveAuditSession() {
                try {
                    sessionStorage.setItem(AUDIT_STORAGE_KEY, JSON.stringify(auditState));
                } catch (e) {}
            }

            function loadAuditSession() {
                try {
                    const raw = sessionStorage.getItem(AUDIT_STORAGE_KEY);
                    if (raw) {
                        auditState = JSON.parse(raw);
                        if (auditState && auditState.location) {
                            renderAuditDashboard();
                        }
                    }
                } catch (e) {}
            }

            function renderAuditDashboard() {
                if (!auditState.location) return;

                auditSetupCard.classList.add('hidden');
                activeAuditDashboard.classList.remove('hidden');

                activeLocationTitle.textContent = auditState.location;

                // Compute counts
                const totalExpected = auditState.expected.length;
                const foundCount = auditState.expected.filter(a => a.found).length;
                const missingCount = totalExpected - foundCount;
                const unexpectedCount = auditState.unexpected.length;

                document.getElementById('metric-expected').textContent = totalExpected;
                document.getElementById('metric-found').textContent = foundCount;
                document.getElementById('metric-missing').textContent = missingCount;
                document.getElementById('metric-unexpected').textContent = unexpectedCount;

                document.getElementById('count-all').textContent = totalExpected + unexpectedCount;
                document.getElementById('count-found').textContent = foundCount;
                document.getElementById('count-missing').textContent = missingCount;
                document.getElementById('count-unexpected').textContent = unexpectedCount;

                const percent = totalExpected > 0 ? Math.round((foundCount / totalExpected) * 100) : 100;
                document.getElementById('audit-progress-bar').style.width = percent + '%';
                document.getElementById('audit-progress-label').textContent = `Found ${foundCount} of ${totalExpected} expected items (${percent}%)`;

                const statusBadge = document.getElementById('audit-status-badge');
                if (missingCount === 0 && totalExpected > 0) {
                    statusBadge.textContent = 'Location 100% verified';
                    statusBadge.className = 'font-semibold text-emerald-700';
                } else {
                    statusBadge.textContent = 'Audit in progress';
                    statusBadge.className = 'font-semibold text-amber-700';
                }

                renderAuditTable();

                if (auditScanInput) {
                    auditScanInput.value = '';
                    auditScanInput.focus();
                }
            }

            window.setAuditFilter = function (filter) {
                auditState.currentFilter = filter;
                document.querySelectorAll('.audit-filter-btn').forEach(function (btn) {
                    btn.classList.remove('bg-slate-200', 'text-slate-900', 'font-semibold');
                    btn.classList.add('text-slate-600', 'font-medium');
                });
                const activeBtn = document.getElementById('filter-btn-' + filter);
                if (activeBtn) {
                    activeBtn.classList.add('bg-slate-200', 'text-slate-900', 'font-semibold');
                    activeBtn.classList.remove('text-slate-600', 'font-medium');
                }
                renderAuditTable();
            };

            function renderAuditTable() {
                if (!auditTbody) return;

                let items = [];

                // Filter items
                if (auditState.currentFilter === 'all') {
                    items = auditState.expected.concat(auditState.unexpected);
                } else if (auditState.currentFilter === 'found') {
                    items = auditState.expected.filter(a => a.found);
                } else if (auditState.currentFilter === 'missing') {
                    items = auditState.expected.filter(a => !a.found);
                } else if (auditState.currentFilter === 'unexpected') {
                    items = auditState.unexpected;
                }

                if (items.length === 0) {
                    auditTbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="p-8 text-center text-sm text-slate-500">
                                No equipment items match the current audit filter.
                            </td>
                        </tr>
                    `;
                    return;
                }

                auditTbody.innerHTML = items.map(function (item) {
                    const isUnexpected = item.is_unexpected === true;
                    const isFound = item.found === true && !isUnexpected;
                    const isMissing = !isFound && !isUnexpected;

                    let badgeHtml = '';
                    if (isFound) {
                        badgeHtml = `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Verified Present
                        </span>`;
                    } else if (isUnexpected) {
                        badgeHtml = `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-[#0f5d8c] border border-sky-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#0f5d8c]"></span> Unexpected Item
                        </span>`;
                    } else {
                        badgeHtml = `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Missing / Unscanned
                        </span>`;
                    }

                    return `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                ${badgeHtml}
                                ${item.scan_time ? `<div class="text-[11px] text-slate-500 tabular-nums mt-0.5">Scanned at ${item.scan_time}</div>` : ''}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs font-semibold text-slate-900 whitespace-nowrap">
                                ${item.asset_no}
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-900">
                                ${item.asset_name}
                                ${item.model ? `<div class="text-xs text-slate-500 font-normal">${item.model}</div>` : ''}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                <div>${item.location || 'Unassigned'}</div>
                                ${item.department ? `<div class="text-slate-400 font-normal">${item.department}</div>` : ''}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                ${getStatusBadgeHtml(item.status)}
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    ${isUnexpected ? `
                                        <a href="/assets/${item.id}/move"
                                           class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-sky-50 text-[#0f5d8c] hover:bg-sky-100 border border-sky-200 text-xs font-medium transition-colors">
                                            Move Here
                                        </a>
                                    ` : ''}
                                    <a href="/assets/${item.id}"
                                       class="min-h-[38px] inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            window.handleAuditScan = async function (e) {
                e.preventDefault();
                const code = (auditScanInput.value || '').trim();
                if (!code) return;

                const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                // Check 1: Does it match an expected item for this room?
                const matchIndex = auditState.expected.findIndex(a => a.asset_no.toLowerCase() === code.toLowerCase());

                if (matchIndex >= 0) {
                    const item = auditState.expected[matchIndex];
                    if (item.found) {
                        showAuditFeedback(`"${item.asset_no}" was already verified earlier in this audit.`, 'amber', timeStr);
                    } else {
                        item.found = true;
                        item.scan_time = timeStr;
                        showAuditFeedback(`Verified: "${item.asset_no}" (${item.asset_name}) is present!`, 'emerald', timeStr);
                    }
                    saveAuditSession();
                    renderAuditDashboard();
                    return;
                }

                // Check 2: Was it already scanned as unexpected?
                const unexpMatch = auditState.unexpected.find(a => a.asset_no.toLowerCase() === code.toLowerCase());
                if (unexpMatch) {
                    showAuditFeedback(`"${unexpMatch.asset_no}" is already recorded as an unexpected item.`, 'amber', timeStr);
                    auditScanInput.value = '';
                    auditScanInput.focus();
                    return;
                }

                // Check 3: Look up in general inventory to see where it actually belongs
                try {
                    const res = await fetch('/api/assets/quick-search?q=' + encodeURIComponent(code));
                    if (res.ok) {
                        const results = await res.json();
                        const foundAsset = results.find(a => a.asset_no.toLowerCase() === code.toLowerCase());

                        if (foundAsset) {
                            auditState.unexpected.push(Object.assign({}, foundAsset, {
                                is_unexpected: true,
                                scan_time: timeStr
                            }));
                            showAuditFeedback(`Unexpected Item: "${foundAsset.asset_no}" belongs to "${foundAsset.location || 'Another Location'}"!`, 'sky', timeStr);
                            saveAuditSession();
                            renderAuditDashboard();
                            return;
                        }
                    }
                } catch (e) {}

                // Check 4: Not found anywhere
                showAuditFeedback(`Unregistered Tag: Code "${code}" is not in the hospital database.`, 'amber', timeStr);
                auditScanInput.value = '';
                auditScanInput.focus();
            };

            function showAuditFeedback(text, type, time) {
                auditFeedbackText.textContent = text;
                auditFeedbackTime.textContent = time;

                if (type === 'emerald') {
                    auditFeedback.className = 'p-3 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-900 border border-emerald-300 flex items-center justify-between';
                } else if (type === 'sky') {
                    auditFeedback.className = 'p-3 rounded-lg text-xs font-semibold bg-sky-50 text-[#0f5d8c] border border-sky-300 flex items-center justify-between';
                } else {
                    auditFeedback.className = 'p-3 rounded-lg text-xs font-semibold bg-amber-50 text-amber-900 border border-amber-300 flex items-center justify-between';
                }

                auditFeedback.classList.remove('hidden');
            }

            // Export to Excel / CSV format
            window.exportAuditToExcel = function () {
                if (!auditState.location) return;

                const loc = auditState.location;
                const now = new Date();
                const dateStr = now.toISOString().split('T')[0];

                let csv = '\uFEFF'; // UTF-8 BOM for Microsoft Excel compatibility
                csv += `Hospital Location Inventory Audit Report\r\n`;
                csv += `Location Audited,"${loc.replace(/"/g, '""')}"\r\n`;
                csv += `Audit Date,"${now.toLocaleDateString()} ${now.toLocaleTimeString()}"\r\n`;
                csv += `Hospital,"Bethel Baptist Hospital, Inc."\r\n`;
                csv += `\r\n`;
                csv += `Audit Result,Asset No.,Equipment Name,Model,Serial No.,Department,Registered Location,Operational Status,Scan Timestamp\r\n`;

                auditState.expected.forEach(function (item) {
                    const res = item.found ? 'Found & Verified' : 'Missing';
                    csv += `"${res}","${item.asset_no}","${(item.asset_name || '').replace(/"/g, '""')}","${(item.model || '').replace(/"/g, '""')}","${(item.serial_no || '').replace(/"/g, '""')}","${(item.department || '').replace(/"/g, '""')}","${(item.location || '').replace(/"/g, '""')}","${item.status || 'In Use'}","${item.scan_time || 'Unscanned'}"\r\n`;
                });

                auditState.unexpected.forEach(function (item) {
                    csv += `"Unexpected Item","${item.asset_no}","${(item.asset_name || '').replace(/"/g, '""')}","${(item.model || '').replace(/"/g, '""')}","${(item.serial_no || '').replace(/"/g, '""')}","${(item.department || '').replace(/"/g, '""')}","${(item.location || '').replace(/"/g, '""')}","${item.status || 'In Use'}","${item.scan_time || ''}"\r\n`;
                });

                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Bethel-Audit-${loc.replace(/[^a-zA-Z0-9]/g, '_')}-${dateStr}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                showAuditFeedback('Audit report downloaded successfully as Excel CSV file.', 'emerald', new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
            };

            // Check if there was an active audit in session
            loadAuditSession();
        });
    </script>
@endsection
