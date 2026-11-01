@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-extrabold tracking-tight text-red-600">Assets</h2>
            <p class="text-base text-neutral-600 mt-1">
                Search, filter, register hospital equipment, or select items to print property tags.
            </p>
        </div>
        <a href="{{ route('assets.create') }}"
           class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-lg shadow-xs transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Asset</span>
        </a>
    </div>

    <!-- Search & Filters -->
    <form method="GET" action="{{ route('assets.index') }}" class="p-5 mb-6 bg-red-50 border-2 border-red-100 rounded-2xl shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <label for="search" class="block text-sm font-bold text-neutral-900 mb-1.5">
                    Search Asset (Asset No., Name, Model, Serial No., Location)
                </label>
                <div class="relative">
                    <svg class="w-5 h-5 text-red-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="search"
                           name="search"
                           type="text"
                           value="{{ $search }}"
                           placeholder="Type Asset No., name, or location..."
                           class="w-full pl-11 pr-4 py-2.5 text-base bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
                </div>
            </div>

            <div class="md:col-span-3">
                <label for="department" class="block text-sm font-bold text-neutral-900 mb-1.5">
                    Department
                </label>
                <select id="department"
                        name="department"
                        class="w-full px-4 py-2.5 text-base bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept }}" @selected($selectedDepartment === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="status" class="block text-sm font-bold text-neutral-900 mb-1.5">
                    Status
                </label>
                <select id="status"
                        name="status"
                        class="w-full px-4 py-2.5 text-base bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected($selectedStatus === $statusOption)>{{ $statusOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-base cursor-pointer transition">
                    <span>Find</span>
                </button>
                @if ($search !== '' || $selectedDepartment !== '' || $selectedStatus !== '')
                    <a href="{{ route('assets.index') }}"
                       class="inline-flex items-center justify-center px-3.5 py-2.5 rounded-xl border-2 border-red-300 bg-white text-red-700 font-bold text-sm hover:bg-red-50 transition">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Print Form + Assets Table -->
    <form id="bulk-print-form" method="GET" action="{{ route('tags.print') }}">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3.5">
            <p class="text-sm font-bold text-neutral-700 tabular-nums">
                Showing {{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }} of {{ $assets->total() }} assets
            </p>
            <button type="submit"
                    id="bulk-print-btn"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-red-600 bg-white hover:bg-red-600 hover:text-white active:translate-y-[1px] text-red-600 font-bold text-sm transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span id="bulk-print-label">Print Selected Tags</span>
            </button>
        </div>

        <div class="overflow-x-auto border-2 border-red-100 rounded-2xl bg-white shadow-xs">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-red-600 text-white text-xs font-extrabold uppercase tracking-wider">
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox"
                                   id="select-all-assets"
                                   aria-label="Select all assets on this page"
                                   class="w-5 h-5 accent-red-600 rounded cursor-pointer">
                        </th>
                        <th class="p-4">Asset No.</th>
                        <th class="p-4">Asset Name</th>
                        <th class="p-4">Model / Serial</th>
                        <th class="p-4">Department &amp; Location</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-100 text-base">
                    @forelse ($assets as $asset)
                        <tr class="hover:bg-red-50 transition">
                            <td class="p-4 text-center">
                                <input type="checkbox"
                                       name="ids[]"
                                       value="{{ $asset->id }}"
                                       aria-label="Select {{ $asset->asset_no }}"
                                       class="asset-checkbox w-5 h-5 accent-red-600 rounded cursor-pointer">
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <a href="{{ route('assets.show', $asset) }}"
                                   class="inline-flex items-center px-2.5 py-1 rounded-md bg-red-50 text-red-700 border border-red-200 font-mono text-sm font-bold hover:bg-red-100 transition">
                                    {{ $asset->asset_no }}
                                </a>
                            </td>
                            <td class="p-4 font-bold text-neutral-900">
                                <a href="{{ route('assets.show', $asset) }}" class="hover:text-red-600 hover:underline">
                                    {{ $asset->asset_name }}
                                </a>
                            </td>
                            <td class="p-4 text-neutral-700">
                                <div class="font-medium text-neutral-900">{{ $asset->model ?: '—' }}</div>
                                @if ($asset->serial_no)
                                    <div class="text-xs font-mono text-neutral-500 mt-0.5">SN: {{ $asset->serial_no }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-neutral-800">
                                <div class="font-bold text-neutral-900">{{ $asset->location ?: '—' }}</div>
                                @if ($asset->department)
                                    <div class="text-xs font-semibold text-neutral-500 mt-0.5">{{ $asset->department }}</div>
                                @endif
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                @include('assets.partials.status-badge', ['status' => $asset->status])
                            </td>
                            <td class="p-4 text-right whitespace-nowrap space-x-1.5">
                                <a href="{{ route('tags.print', ['ids' => [$asset->id]]) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold text-xs transition">
                                    Print Tag
                                </a>
                                <a href="{{ route('assets.show', $asset) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-700 font-bold text-xs transition">
                                    View
                                </a>
                                <a href="{{ route('assets.edit', $asset) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-700 font-bold text-xs transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-neutral-600">
                                <p class="text-lg font-bold text-neutral-900 mb-1">No assets found</p>
                                <p class="text-base mb-4">Click "Add Asset" above to register an item.</p>
                                <a href="{{ route('assets.create') }}"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-600 text-white font-bold text-base hover:bg-red-700 transition">
                                    <span>Add First Asset</span>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if ($assets->hasPages())
        <div class="mt-6">
            {{ $assets->links() }}
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-assets');
            const checkboxes = Array.from(document.querySelectorAll('.asset-checkbox'));
            const label = document.getElementById('bulk-print-label');

            function updateBulkCount() {
                const count = checkboxes.filter(cb => cb.checked).length;
                if (label) {
                    label.textContent = count > 0 ? `Print Selected Tags (${count})` : 'Print Selected Tags';
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
                    updateBulkCount();
                });
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateBulkCount));
        });
    </script>
@endsection
