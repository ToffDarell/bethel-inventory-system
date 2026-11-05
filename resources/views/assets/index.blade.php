@extends('layouts.app')

@section('content')
    <x-page-header
        title="Assets"
        subtitle="Search, filter, register hospital equipment, or view archived records"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('assets.find')">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Find Asset</span>
            </x-button>

            <x-button variant="primary" :href="route('assets.create')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Asset</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Tab Navigation (Active Inventory vs Archived) -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 mb-5">
        <a href="{{ route('assets.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all {{ $activeTab === 'active' ? 'bg-primary text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span>Active Inventory</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono tabular-nums {{ $activeTab === 'active' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                {{ number_format($activeCount) }}
            </span>
        </a>

        <a href="{{ route('assets.index', ['tab' => 'archived']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all {{ $activeTab === 'archived' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <span>Archive</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono tabular-nums {{ $activeTab === 'archived' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                {{ number_format($archivedCount) }}
            </span>
        </a>
    </div>

    <!-- Sticky Search & Filter Bar -->
    <div class="sticky top-14 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-xl p-3.5 shadow-2xs mb-5 transition-shadow">
        <form method="GET" action="{{ route('assets.index') }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-12 gap-2.5 items-end">
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-3">
                    <label for="search" class="block text-xs font-semibold text-slate-700 mb-1">
                        Search Equipment
                    </label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="search"
                               name="search"
                               type="text"
                               value="{{ $search }}"
                               placeholder="Asset No., description, brand, room..."
                               class="w-full min-h-[44px] pl-10 pr-4 py-2 text-xs bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label for="category" class="block text-xs font-semibold text-slate-700 mb-1">
                        Category
                    </label>
                    <select id="category"
                            name="category"
                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($selectedCategory == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="department" class="block text-xs font-semibold text-slate-700 mb-1">
                        Department
                    </label>
                    <select id="department"
                            name="department"
                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                        <option value="">All departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}" @selected($selectedDepartment === $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-1">
                    <label for="section" class="block text-xs font-semibold text-slate-700 mb-1">
                        Section
                    </label>
                    <select id="section"
                            name="section"
                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                        <option value="">All sections</option>
                        @foreach ($sections ?? [] as $sec)
                            <option value="{{ $sec }}" @selected(($selectedSection ?? '') === $sec)>{{ $sec }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-1">
                    <label for="status" class="block text-xs font-semibold text-slate-700 mb-1">
                        Status
                    </label>
                    <select id="status"
                            name="status"
                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}" @selected($selectedStatus === $statusOption)>{{ $statusOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="condition" class="block text-xs font-semibold text-slate-700 mb-1">
                        Condition
                    </label>
                    <select id="condition"
                            name="condition"
                            class="w-full min-h-[44px] px-3 py-2 text-xs bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                        <option value="">All conditions</option>
                        <option value="Not set" @selected(($selectedCondition ?? '') === 'Not set')>Not set</option>
                        @foreach ($conditions ?? [] as $condOption)
                            <option value="{{ $condOption }}" @selected(($selectedCondition ?? '') === $condOption)>{{ $condOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-1 flex items-center gap-1.5">
                    <button type="submit"
                            class="flex-1 min-h-[44px] inline-flex items-center justify-center gap-1 px-3 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs cursor-pointer shadow-2xs transition-colors">
                        <span>Filter</span>
                    </button>
                    @if ($search !== '' || $selectedDepartment !== '' || ($selectedSection ?? '') !== '' || $selectedStatus !== '' || ($selectedCondition ?? '') !== '' || $selectedCategory !== '')
                        <a href="{{ route('assets.index', ['tab' => $activeTab]) }}"
                           title="Clear search filters"
                           class="min-h-[44px] inline-flex items-center justify-center px-2.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 hover:border-slate-400 transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if ($activeTab === 'archived')
        <!-- Archived Tab Content -->
        <div class="mb-4 p-3.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center gap-3 text-xs">
            <svg class="w-5 h-5 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>
                <strong>Archive Mode:</strong> Equipment records here are excluded from active counts and audit scans. Any authorized staff member can restore an item, and Administrators can permanently delete records.
            </p>
        </div>

        @if ($assets->count() > 0)
            <div class="flex items-center justify-between gap-3 mb-3">
                <p class="text-xs text-slate-600 tabular-nums">
                    Showing <strong class="font-semibold text-slate-900">{{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }}</strong> of <strong class="font-semibold text-slate-900">{{ $assets->total() }}</strong> archived items
                </p>
            </div>

            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="py-3 px-4 w-32 whitespace-nowrap">Asset No.</th>
                        <th class="py-3 px-4">Equipment &amp; Former Location</th>
                        <th class="py-3 px-4 w-36 whitespace-nowrap">Category</th>
                        <th class="py-3 px-4 w-40">Model &amp; Serial</th>
                        <th class="py-3 px-4 w-28 whitespace-nowrap">Status</th>
                        <th class="py-3 px-4 w-36 whitespace-nowrap">Archived On</th>
                        <th class="py-3 px-4 w-36 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </x-slot:header>

                @foreach ($assets as $asset)
                    <tr class="hover:bg-slate-50/90 transition-colors cursor-pointer group [&>td]:h-[52px]"
                        onclick="window.location.href='{{ route('assets.show', $asset) }}'">
                        <td class="py-2.5 px-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-mono text-xs font-semibold group-hover:border-slate-300 transition-colors">
                                {{ $asset->asset_no }}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 min-w-[180px] max-w-[280px]">
                            <a href="{{ route('assets.show', $asset) }}"
                               class="font-semibold text-slate-900 group-hover:text-primary block truncate transition-colors"
                               title="{{ $asset->asset_name }}">
                                {{ $asset->asset_name }}
                            </a>
                            <p class="text-[11px] text-slate-600 truncate mt-0.5"
                               title="{{ $asset->location ?: 'No location' }} &bull; {{ $asset->department ?: 'Unassigned' }}">
                                {{ $asset->location ?: 'No location' }} &bull; {{ $asset->department ?: 'Unassigned' }}
                            </p>
                        </td>
                        <td class="py-2.5 px-4 whitespace-nowrap">
                            <span class="text-xs text-slate-700 font-medium truncate block max-w-[140px]" title="{{ $asset->category?->name ?? 'Uncategorized' }}">
                                {{ $asset->category?->name ?? 'Uncategorized' }}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 min-w-[140px] max-w-[200px]">
                            <p class="text-xs text-slate-800 font-medium truncate" title="{{ $asset->model ?: '-' }}">
                                {{ $asset->model ?: '-' }}
                            </p>
                            @if ($asset->serial_no)
                                <p class="text-[11px] font-mono text-slate-600 truncate mt-0.5" title="SN: {{ $asset->serial_no }}">
                                    SN: {{ $asset->serial_no }}
                                </p>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 whitespace-nowrap">
                            <x-badge :status="$asset->status" />
                        </td>
                        <td class="py-2.5 px-4 whitespace-nowrap text-xs text-slate-600 tabular-nums">
                            {{ $asset->deleted_at ? $asset->deleted_at->format('M d, Y') : '-' }}
                        </td>
                        <td class="py-2.5 px-4 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                            <div class="inline-flex items-center justify-end gap-1">
                                <x-icon-button :href="route('assets.show', $asset)" title="View Details" size="sm">
                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span class="sr-only">View</span>
                                </x-icon-button>

                                <button type="button"
                                        onclick="openRestoreModal({{ $asset->id }}, '{{ addslashes($asset->asset_no) }}', '{{ addslashes($asset->asset_name) }}')"
                                        title="Restore to Active Inventory"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-emerald-700 hover:text-emerald-900 hover:bg-emerald-50 transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span class="sr-only">Restore</span>
                                </button>

                                @if (auth()->user()?->isAdmin())
                                    <button type="button"
                                            onclick="openForceDeleteModal({{ $asset->id }}, '{{ addslashes($asset->asset_no) }}', '{{ addslashes($asset->asset_name) }}')"
                                            title="Permanently Delete"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand-red hover:text-red-700 hover:bg-red-50 transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span class="sr-only">Permanently Delete</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <x-empty-state
                title="Archive is empty"
                description="No hospital equipment records are currently in the archive"
            >
                <x-slot:action>
                    @if ($search !== '' || $selectedDepartment !== '' || $selectedStatus !== '')
                        <x-button variant="secondary" :href="route('assets.index', ['tab' => 'archived'])">
                            Clear Filters
                        </x-button>
                    @else
                        <x-button variant="secondary" :href="route('assets.index')">
                            Return to Active Inventory
                        </x-button>
                    @endif
                </x-slot:action>
            </x-empty-state>
        @endif
    @else
        <!-- Active Inventory Tab Content -->
        <form id="bulk-print-form" method="GET" action="{{ route('tags.print') }}">
            <!-- Contextual Bulk-Select Bar (Appears when rows are ticked) -->
            <div id="bulk-select-bar"
                 class="hidden mb-4 p-3 rounded-xl bg-white border border-primary/30 text-slate-900 flex flex-wrap items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <span id="bulk-selected-count" class="font-mono text-xs font-bold text-primary bg-primary-soft border border-primary/20 px-2.5 py-1 rounded-md tabular-nums">
                        0 selected
                    </span>
                    <span class="text-xs text-slate-600">Items queued for A4 property tag sheet formatting</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            id="bulk-clear-btn"
                            class="min-h-[44px] px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 cursor-pointer transition-colors">
                        Clear selection
                    </button>
                    <button type="submit"
                            class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs cursor-pointer shadow-2xs transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span id="bulk-action-text">Print Selected Tags</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <p class="text-xs text-slate-600 tabular-nums">
                    Showing <strong class="font-semibold text-slate-900">{{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }}</strong> of <strong class="font-semibold text-slate-900">{{ $assets->total() }}</strong> assets
                </p>
                <div class="flex items-center gap-2">
                    <!-- Columns Toggle Dropdown -->
                    <div class="relative inline-block text-left" id="columns-menu-container">
                        <button type="button"
                                onclick="toggleColumnsMenu()"
                                id="columns-menu-button"
                                class="min-h-[36px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h17.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z"/>
                            </svg>
                            <span>Columns</span>
                        </button>
                        <div id="columns-dropdown"
                             class="hidden absolute right-0 mt-2 w-48 rounded-xl bg-white border border-slate-200 shadow-lg p-2.5 z-30 text-xs space-y-2">
                            <p class="font-semibold text-slate-800 px-1 pb-1 border-b border-slate-100">Toggle Columns</p>
                            <label class="flex items-center gap-2 px-1 py-1 rounded hover:bg-slate-50 cursor-pointer text-slate-700">
                                <input type="checkbox" id="toggle-col-brand" onchange="toggleColumn('brand', this.checked)" class="rounded accent-primary">
                                <span>Brand</span>
                            </label>
                            <label class="flex items-center gap-2 px-1 py-1 rounded hover:bg-slate-50 cursor-pointer text-slate-700">
                                <input type="checkbox" id="toggle-col-personnel" onchange="toggleColumn('personnel', this.checked)" class="rounded accent-primary">
                                <span>Assigned Personnel</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit"
                            id="bulk-print-btn"
                            class="min-h-[36px] inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span id="bulk-print-label">Print Selected Tags</span>
                    </button>
                </div>
            </div>

            @if ($assets->count() > 0)
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="py-3 px-4 w-10 text-center">
                                <input type="checkbox"
                                       id="select-all-assets"
                                       aria-label="Select all assets on this page"
                                       class="w-4 h-4 accent-primary rounded cursor-pointer">
                            </th>
                            <th class="py-3 px-4 w-32 whitespace-nowrap">Asset No.</th>
                            <th class="py-3 px-4">Equipment &amp; Location</th>
                            <th class="py-3 px-4 w-32 whitespace-nowrap">Section</th>
                            <th class="py-3 px-4 w-32 whitespace-nowrap">Category</th>
                            <th class="col-brand py-3 px-4 w-32 whitespace-nowrap" style="display: none;">Brand</th>
                            <th class="py-3 px-4 w-36">Model &amp; Serial</th>
                            <th class="col-personnel py-3 px-4 w-36 whitespace-nowrap" style="display: none;">Assigned Personnel</th>
                            <th class="py-3 px-4 w-28 whitespace-nowrap">Status</th>
                            <th class="py-3 px-4 w-36 whitespace-nowrap">Asset Condition</th>
                            @if (auth()->user()?->isAdmin())
                                <th class="py-3 px-4 w-36 text-right whitespace-nowrap">Current Value</th>
                            @endif
                            <th class="py-3 px-4 w-36 text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </x-slot:header>

                    @foreach ($assets as $asset)
                        <tr class="hover:bg-slate-50/90 transition-colors cursor-pointer group [&>td]:h-[52px]"
                            onclick="window.location.href='{{ route('assets.show', $asset) }}'">
                            <td class="py-2.5 px-4 text-center" onclick="event.stopPropagation()">
                                <input type="checkbox"
                                       name="ids[]"
                                       value="{{ $asset->id }}"
                                       aria-label="Select {{ $asset->asset_no }}"
                                       class="asset-checkbox w-4 h-4 accent-primary rounded cursor-pointer">
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-mono text-xs font-semibold group-hover:border-slate-300 transition-colors">
                                    {{ $asset->asset_no }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 min-w-[180px] max-w-[280px]">
                                <a href="{{ route('assets.show', $asset) }}"
                                   class="font-semibold text-slate-900 group-hover:text-primary block truncate transition-colors"
                                   title="{{ $asset->asset_name }}">
                                    {{ $asset->asset_name }}
                                </a>
                                <p class="text-[11px] text-slate-600 truncate mt-0.5"
                                   title="{{ $asset->location ?: 'No location' }} &bull; {{ $asset->department ?: 'Unassigned' }}">
                                    {{ $asset->location ?: 'No location' }} &bull; {{ $asset->department ?: 'Unassigned' }}
                                </p>
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <span class="text-xs text-slate-700 font-medium truncate block max-w-[120px]" title="{{ $asset->section ?: '-' }}">
                                    {{ $asset->section ?: '-' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <span class="text-xs text-slate-700 font-medium truncate block max-w-[120px]" title="{{ $asset->category?->name ?? 'Uncategorized' }}">
                                    {{ $asset->category?->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="col-brand py-2.5 px-4 whitespace-nowrap" style="display: none;">
                                <span class="text-xs text-slate-800 font-medium truncate block max-w-[120px]" title="{{ $asset->brand ?: '-' }}">
                                    {{ $asset->brand ?: '-' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 min-w-[130px] max-w-[180px]">
                                <p class="text-xs text-slate-800 font-medium truncate" title="{{ $asset->model ?: '-' }}">
                                    {{ $asset->model ?: '-' }}
                                </p>
                                @if ($asset->serial_no)
                                    <p class="text-[11px] font-mono text-slate-600 truncate mt-0.5" title="SN: {{ $asset->serial_no }}">
                                        SN: {{ $asset->serial_no }}
                                    </p>
                                @endif
                            </td>
                            <td class="col-personnel py-2.5 px-4 whitespace-nowrap" style="display: none;">
                                <span class="text-xs text-slate-700 font-medium truncate block max-w-[130px]" title="{{ $asset->assigned_personnel ?: '-' }}">
                                    {{ $asset->assigned_personnel ?: '-' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <x-badge :status="$asset->status" />
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <x-condition-badge :condition="$asset->asset_condition" />
                            </td>
                            @if (auth()->user()?->isAdmin())
                                @php
                                    $valResult = $depreciationService->calculateForAsset($asset);
                                @endphp
                                <td class="py-2.5 px-4 text-right whitespace-nowrap font-mono text-xs font-semibold text-slate-900">
                                    @if ($valResult->isCalculable())
                                        ₱{{ number_format($valResult->getCurrentValue(), 2) }}
                                    @elseif ($asset->cost !== null)
                                        ₱{{ number_format($asset->cost, 2) }}
                                    @else
                                        <span class="text-slate-400 font-normal">No cost</span>
                                    @endif
                                </td>
                            @endif
                            <td class="py-2.5 px-4 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <x-icon-button :href="route('assets.show', $asset)" title="View Asset" size="sm">
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span class="sr-only">View</span>
                                    </x-icon-button>
                                    <x-icon-button :href="route('tags.print', ['ids' => [$asset->id]])" title="Print Tag" size="sm">
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        <span class="sr-only">Print Tag</span>
                                    </x-icon-button>
                                    <x-icon-button :href="route('assets.edit', $asset)" title="Edit Asset" size="sm">
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span class="sr-only">Edit Asset</span>
                                    </x-icon-button>
                                    <x-icon-button :href="route('assets.move.create', $asset)" title="Move Asset" size="sm">
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        <span class="sr-only">Move Asset</span>
                                    </x-icon-button>
                                    <button type="button"
                                            onclick="openArchiveModal({{ $asset->id }}, '{{ addslashes($asset->asset_no) }}', '{{ addslashes($asset->asset_name) }}')"
                                            title="Archive Asset"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-amber-700 hover:bg-amber-50 transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                        </svg>
                                        <span class="sr-only">Archive</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            @else
                <x-empty-state
                    title="No matching hospital assets found"
                    description="Try clearing your search filters, or register a new piece of hospital equipment"
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
    @endif

    @if ($assets->hasPages())
        <div class="mt-5">
            {{ $assets->links() }}
        </div>
    @endif

    <!-- Archive Confirmation Modal -->
    <div id="archive-modal"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden transition-opacity"
         role="dialog"
         aria-modal="true"
         aria-labelledby="archive-modal-title"
         onclick="closeArchiveModal()">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150"
             onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-full bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 id="archive-modal-title" class="text-base font-bold text-slate-900 leading-tight">
                            Archive Asset
                        </h3>
                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                            Move <strong id="archive-asset-name" class="font-semibold text-slate-900"></strong> (<span id="archive-asset-no" class="font-mono text-slate-700"></span>) to the archive? It will be removed from active inventory and audits, but its history is preserved.
                        </p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button"
                            id="archive-cancel-btn"
                            onclick="closeArchiveModal()"
                            class="min-h-[40px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <form id="archive-form" method="POST" action="" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="min-h-[40px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-2xs focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1 transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            <span>Move to Archive</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="restore-modal"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden transition-opacity"
         role="dialog"
         aria-modal="true"
         aria-labelledby="restore-modal-title"
         onclick="closeRestoreModal()">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150"
             onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 id="restore-modal-title" class="text-base font-bold text-slate-900 leading-tight">
                            Restore to Active Inventory
                        </h3>
                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                            Restore <strong id="restore-asset-name" class="font-semibold text-slate-900"></strong> (<span id="restore-asset-no" class="font-mono text-slate-700"></span>) back to active inventory? It will immediately appear in inventory lists, searches, and property audits.
                        </p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button"
                            id="restore-cancel-btn"
                            onclick="closeRestoreModal()"
                            class="min-h-[40px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <form id="restore-form" method="POST" action="" class="inline">
                        @csrf
                        <button type="submit"
                                class="min-h-[40px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-2xs focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1 transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Restore Asset</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()?->isAdmin())
        <!-- Permanent Delete Confirmation Modal -->
        <div id="force-delete-modal"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden transition-opacity"
             role="dialog"
             aria-modal="true"
             aria-labelledby="force-delete-modal-title"
             onclick="closeForceDeleteModal()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150"
                 onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-full bg-red-50 text-brand-red border border-red-200 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-brand-red" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="force-delete-modal-title" class="text-base font-bold text-slate-900 leading-tight">
                                Permanently Delete Asset
                            </h3>
                            <p class="text-xs text-red-600 font-semibold mt-1">
                                Caution: This action is permanent and cannot be undone!
                            </p>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Are you sure you want to completely erase <strong id="force-delete-asset-name" class="font-semibold text-slate-900"></strong> (<span id="force-delete-asset-no" class="font-mono text-slate-700"></span>)? All records and transfer history will be deleted.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button"
                                id="force-delete-cancel-btn"
                                onclick="closeForceDeleteModal()"
                                class="min-h-[40px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <form id="force-delete-form" method="POST" action="" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="min-h-[40px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-brand-red hover:bg-red-700 rounded-lg shadow-2xs focus:outline-none focus:ring-2 focus:ring-brand-red focus:ring-offset-1 transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Permanently Delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        function openArchiveModal(id, no, name) {
            const modal = document.getElementById('archive-modal');
            const form = document.getElementById('archive-form');
            if (!modal || !form) return;
            form.action = `/assets/${id}`;
            document.getElementById('archive-asset-name').textContent = name;
            document.getElementById('archive-asset-no').textContent = no;
            modal.classList.remove('hidden');
            setTimeout(() => document.getElementById('archive-cancel-btn')?.focus(), 50);
        }

        function closeArchiveModal() {
            const modal = document.getElementById('archive-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openRestoreModal(id, no, name) {
            const modal = document.getElementById('restore-modal');
            const form = document.getElementById('restore-form');
            if (!modal || !form) return;
            form.action = `/assets/${id}/restore`;
            document.getElementById('restore-asset-name').textContent = name;
            document.getElementById('restore-asset-no').textContent = no;
            modal.classList.remove('hidden');
            setTimeout(() => document.getElementById('restore-cancel-btn')?.focus(), 50);
        }

        function closeRestoreModal() {
            const modal = document.getElementById('restore-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openForceDeleteModal(id, no, name) {
            const modal = document.getElementById('force-delete-modal');
            const form = document.getElementById('force-delete-form');
            if (!modal || !form) return;
            form.action = `/assets/${id}/force-delete`;
            document.getElementById('force-delete-asset-name').textContent = name;
            document.getElementById('force-delete-asset-no').textContent = no;
            modal.classList.remove('hidden');
            setTimeout(() => document.getElementById('force-delete-cancel-btn')?.focus(), 50);
        }

        function closeForceDeleteModal() {
            const modal = document.getElementById('force-delete-modal');
            if (modal) modal.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeArchiveModal();
                closeRestoreModal();
                closeForceDeleteModal();
            }
        });

        const COL_STORAGE_KEY = 'bethel_assets_columns_prefs';

        function getColumnPrefs() {
            try {
                const stored = localStorage.getItem(COL_STORAGE_KEY);
                return stored ? JSON.parse(stored) : { brand: false, personnel: false };
            } catch (e) {
                return { brand: false, personnel: false };
            }
        }

        function saveColumnPrefs(prefs) {
            try {
                localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(prefs));
            } catch (e) {}
        }

        function applyColumnVisibility() {
            const prefs = getColumnPrefs();
            document.querySelectorAll('.col-brand').forEach(el => {
                el.style.display = prefs.brand ? '' : 'none';
            });
            document.querySelectorAll('.col-personnel').forEach(el => {
                el.style.display = prefs.personnel ? '' : 'none';
            });
            const brandCheck = document.getElementById('toggle-col-brand');
            if (brandCheck) brandCheck.checked = !!prefs.brand;
            const personnelCheck = document.getElementById('toggle-col-personnel');
            if (personnelCheck) personnelCheck.checked = !!prefs.personnel;
        }

        function toggleColumn(colName, isVisible) {
            const prefs = getColumnPrefs();
            prefs[colName] = isVisible;
            saveColumnPrefs(prefs);
            applyColumnVisibility();
        }

        function toggleColumnsMenu() {
            const dd = document.getElementById('columns-dropdown');
            if (dd) dd.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const container = document.getElementById('columns-menu-container');
            const dd = document.getElementById('columns-dropdown');
            if (container && dd && !container.contains(e.target)) {
                dd.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            applyColumnVisibility();
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
                        if (bulkActionText) bulkActionText.textContent = `Print Selected Tags (${count})`;
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
