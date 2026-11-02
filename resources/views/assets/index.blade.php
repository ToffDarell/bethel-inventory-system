@extends('layouts.app')

@section('content')
    <x-page-header
        title="Assets"
        subtitle="Search, filter, register hospital equipment, or select items to print property tags."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('scan.index')">
                <svg class="w-4 h-4 text-[#0f5d8c]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Scan Tag</span>
            </x-button>

            <x-button variant="primary" :href="route('assets.create')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Asset</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Sticky Search & Filter Bar -->
    <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-xl p-4 shadow-xs mb-5">
        <form method="GET" action="{{ route('assets.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5 items-end">
                <div class="md:col-span-5">
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Search Asset (No., Name, Model, Serial, Location)
                    </label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="search"
                               name="search"
                               type="text"
                               value="{{ $search }}"
                               placeholder="Type Asset No., name, or location..."
                               class="w-full min-h-[44px] pl-10 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition">
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label for="department" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Department
                    </label>
                    <select id="department"
                            name="department"
                            class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}" @selected($selectedDepartment === $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Status
                    </label>
                    <select id="status"
                            name="status"
                            class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}" @selected($selectedStatus === $statusOption)>{{ $statusOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 min-h-[44px] inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg border border-[#0f5d8c] bg-sky-50 hover:bg-sky-100 text-[#0f5d8c] font-semibold text-sm cursor-pointer transition">
                        <span>Filter</span>
                    </button>
                    @if ($search !== '' || $selectedDepartment !== '' || $selectedStatus !== '')
                        <a href="{{ route('assets.index') }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 transition">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Print Form + Assets Table -->
    <form id="bulk-print-form" method="GET" action="{{ route('tags.print') }}">
        <!-- Contextual Bulk-Select Bar (appears when rows are ticked) -->
        <div id="bulk-select-bar"
             class="hidden mb-4 p-3.5 rounded-xl bg-sky-50 border border-sky-300 text-slate-900 flex flex-wrap items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5">
                <span id="bulk-selected-count" class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#0f5d8c] text-white">
                    0 selected
                </span>
                <span class="text-sm font-medium text-slate-700">Ready to format on an A4 property tag sheet.</span>
            </div>
            <div class="flex items-center gap-2.5">
                <button type="button"
                        id="bulk-clear-btn"
                        class="min-h-[44px] px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 cursor-pointer">
                    Clear selection
                </button>
                <button type="submit"
                        class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a6e] text-white font-semibold text-xs shadow-xs cursor-pointer transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span id="bulk-action-text">Print Tags</span>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <p class="text-sm font-medium text-slate-600 tabular-nums">
                Showing <strong class="text-slate-900">{{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }}</strong> of <strong class="text-slate-900">{{ $assets->total() }}</strong> assets
            </p>
            <button type="submit"
                    id="bulk-print-btn"
                    class="min-h-[44px] inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition cursor-pointer shadow-2xs">
                <svg class="w-4 h-4 text-[#0f5d8c]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span id="bulk-print-label">Print Selected Tags</span>
            </button>
        </div>

        @if ($assets->count() > 0)
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">
                            <input type="checkbox"
                                   id="select-all-assets"
                                   aria-label="Select all assets on this page"
                                   class="w-4 h-4 accent-[#0f5d8c] rounded cursor-pointer">
                        </th>
                        <th class="py-3.5 px-4">Asset No.</th>
                        <th class="py-3.5 px-4">Asset Name</th>
                        <th class="py-3.5 px-4">Model / Serial</th>
                        <th class="py-3.5 px-4">Department &amp; Location</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </x-slot:header>

                @foreach ($assets as $asset)
                    <tr>
                        <td class="py-3.5 px-4 text-center">
                            <input type="checkbox"
                                   name="ids[]"
                                   value="{{ $asset->id }}"
                                   aria-label="Select {{ $asset->asset_no }}"
                                   class="asset-checkbox w-4 h-4 accent-[#0f5d8c] rounded cursor-pointer">
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <a href="{{ route('assets.show', $asset) }}"
                               class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-[#0f5d8c] border border-slate-200 font-mono text-xs font-bold hover:bg-sky-50 hover:border-sky-200 transition">
                                {{ $asset->asset_no }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-slate-900">
                            <a href="{{ route('assets.show', $asset) }}" class="hover:text-[#0f5d8c] hover:underline">
                                {{ $asset->asset_name }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            <div class="font-medium text-slate-800">{{ $asset->model ?: '—' }}</div>
                            @if ($asset->serial_no)
                                <div class="text-xs font-mono text-slate-500 mt-0.5">SN: {{ $asset->serial_no }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-700">
                            <div class="font-semibold text-slate-900">{{ $asset->location ?: '—' }}</div>
                            @if ($asset->department)
                                <div class="text-xs text-slate-500 mt-0.5">{{ $asset->department }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <x-badge :status="$asset->status" />
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <a href="{{ route('assets.show', $asset) }}"
                                   class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition">
                                    View
                                </a>
                                <a href="{{ route('tags.print', ['ids' => [$asset->id]]) }}"
                                   class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-[#0f5d8c] font-semibold text-xs transition">
                                    Print Tag
                                </a>
                                <a href="{{ route('assets.move.create', $asset) }}"
                                   class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition">
                                    Move
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <x-empty-state
                title="No matching hospital assets found"
                description="Try clearing your search filters, or register a new piece of hospital equipment."
            >
                <x-slot:action>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($search !== '' || $selectedDepartment !== '' || $selectedStatus !== '')
                            <x-button variant="secondary" :href="route('assets.index')">
                                Clear Filters
                            </x-button>
                        @endif
                        <x-button variant="primary" :href="route('assets.create')">
                            Add Asset
                        </x-button>
                    </div>
                </x-slot:action>
            </x-empty-state>
        @endif
    </form>

    @if ($assets->hasPages())
        <div class="mt-5">
            {{ $assets->links() }}
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-assets');
            const checkboxes = Array.from(document.querySelectorAll('.asset-checkbox'));
            const label = document.getElementById('bulk-print-label');
            const bulkBar = document.getElementById('bulk-select-bar');
            const bulkCountBadge = document.getElementById('bulk-selected-count');
            const bulkActionText = document.getElementById('bulk-action-text');
            const bulkClearBtn = document.getElementById('bulk-clear-btn');

            function updateBulkCount() {
                const count = checkboxes.filter(cb => cb.checked).length;
                if (label) {
                    label.textContent = count > 0 ? `Print Selected Tags (${count})` : 'Print Selected Tags';
                }
                if (bulkBar) {
                    if (count > 0) {
                        bulkBar.classList.remove('hidden');
                        if (bulkCountBadge) bulkCountBadge.textContent = `${count} selected`;
                        if (bulkActionText) bulkActionText.textContent = `Print Tags (${count})`;
                    } else {
                        bulkBar.classList.add('hidden');
                    }
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
                    updateBulkCount();
                });
            }

            if (bulkClearBtn) {
                bulkClearBtn.addEventListener('click', function () {
                    if (selectAll) selectAll.checked = false;
                    checkboxes.forEach(cb => { cb.checked = false; });
                    updateBulkCount();
                });
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateBulkCount));
        });
    </script>
@endsection
