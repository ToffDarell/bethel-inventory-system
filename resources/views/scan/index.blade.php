@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <x-page-header
            title="Scan Property Tag"
            subtitle="Quick barcode and QR tag lookup for equipment on hospital floors"
        />

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('scan-input');
            const form = document.getElementById('scan-form');
            const list = document.getElementById('recent-scans-list');
            const emptyNotice = document.getElementById('no-recent-scans');
            const clearBtn = document.getElementById('clear-recent-scans-btn');
            const previewPanel = document.getElementById('asset-preview-panel');
            const inlineNotFound = document.getElementById('inline-not-found');
            const STORAGE_KEY = 'bbh_recent_scans_history';

            // Auto-focus maintenance
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

                // Save to history and update list
                saveRecentScan(asset);
                renderRecentScans();

                // Re-focus input ready for next scan
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

            // Client-side quick lookup for inline preview
            async function performInlineLookup(query) {
                const clean = (query || '').trim();
                if (!clean) return false;

                try {
                    const res = await fetch('/api/assets/quick-search?q=' + encodeURIComponent(clean));
                    if (!res.ok) return false;
                    const assets = await res.json();

                    if (Array.isArray(assets) && assets.length > 0) {
                        // Look for an exact match by asset_no
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

                    // Try client-side fast inline lookup first
                    e.preventDefault();
                    const found = await performInlineLookup(val);
                    if (!found) {
                        // If not found in live quick-search or multiple ambiguous results, proceed with regular lookup submission
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
        });
    </script>
@endsection
