@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <x-page-header
            title="Scan Property Tag"
            subtitle="Scan an equipment barcode or QR tag with any USB handheld scanner, or type an Asset No."
        />

        <!-- Not Found State -->
        @if ($errors->any())
            <div class="p-5 rounded-xl bg-amber-50/90 border border-amber-300 text-amber-950 shadow-2xs" role="alert" tabindex="-1">
                <div class="flex items-start gap-3.5">
                    <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-semibold text-amber-950">
                            {{ $errors->first('q') }}
                        </h2>
                        @if (! empty($notFoundCode))
                            <p class="text-xs text-amber-800 mt-1">
                                Tag code <span class="font-mono font-medium text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded">{{ $notFoundCode }}</span> is not currently registered in the hospital database.
                            </p>
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <x-button variant="primary" :href="route('assets.create', ['asset_no' => $notFoundCode])">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Register as New Asset with "{{ $notFoundCode }}"</span>
                                </x-button>
                                <x-button variant="secondary" :href="route('assets.index', ['search' => $notFoundCode])">
                                    <span>Search All Inventory Records</span>
                                </x-button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Large Centered Scan Box (Ready State) -->
        <x-card padding="p-8">
            <form id="scan-form" method="GET" action="{{ route('scan.lookup') }}" class="space-y-6 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-700">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                </div>

                <div>
                    <label for="scan-input" class="block text-lg font-semibold text-slate-900 mb-1">
                        Barcode &amp; QR Tag Scanner
                    </label>
                    <p class="text-xs text-slate-500">
                        Aim your barcode or QR scanner at the property tag, or type the Asset No. manually
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 max-w-xl mx-auto">
                    <div class="relative w-full flex-1">
                        <input id="scan-input"
                               name="q"
                               type="text"
                               autofocus
                               autocomplete="off"
                               value="{{ old('q', $notFoundCode ?? '') }}"
                               placeholder="e.g. BBHIND-S300002315 or BBH-000001"
                               class="w-full min-h-[52px] px-4 py-3 text-lg font-mono font-medium text-center sm:text-left bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                    </div>

                    <button type="submit"
                            class="w-full sm:w-auto min-h-[52px] inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-[#b91c1c] hover:bg-[#991b1b] text-white font-medium text-sm shadow-2xs transition-colors cursor-pointer shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Find Asset</span>
                    </button>
                </div>

                <!-- Ready State Indicator -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-xs text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span><strong>Ready to scan:</strong> Keep cursor in the box above. USB scanners will type the code and open the asset record automatically.</span>
                </div>
            </form>
        </x-card>

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
                            class="text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors hidden cursor-pointer">
                        Clear list
                    </button>
                </div>
            </x-slot:header>

            <div id="recent-scans-container" class="divide-y divide-slate-100">
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

            function saveRecentScan(code) {
                if (!code || !code.trim()) return;
                const clean = code.trim().toUpperCase();
                let scans = loadRecentScans().filter(s => s.code !== clean);
                scans.unshift({
                    code: clean,
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });
                if (scans.length > 5) scans = scans.slice(0, 5);
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(scans));
                } catch (e) {}
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

                list.innerHTML = scans.map(s => `
                    <li class="px-6 py-3.5 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-mono text-xs font-medium">
                                ${s.code}
                            </span>
                            <span class="text-xs text-slate-500 font-mono tabular-nums">Scanned at ${s.time}</span>
                        </div>
                        <a href="{{ route('scan.lookup') }}?q=${encodeURIComponent(s.code)}"
                           class="min-h-[36px] inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
                            Open Asset &rarr;
                        </a>
                    </li>
                `).join('');
            }

            if (form && input) {
                form.addEventListener('submit', function () {
                    saveRecentScan(input.value);
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
