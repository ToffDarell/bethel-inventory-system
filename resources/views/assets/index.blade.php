@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">Assets</h2>
            <p class="text-base text-slate-600 mt-1">
                Search, filter, register hospital equipment, or select items to print property tags.
            </p>
        </div>
        <a href="{{ route('assets.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Asset</span>
        </a>
    </div>

    <!-- Search & Filters -->
    <form method="GET" action="{{ route('assets.index') }}" class="p-5 mb-6 bg-slate-50 border-2 border-slate-200 rounded-xl">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <label for="search" class="block text-sm font-bold text-slate-900 mb-1.5">
                    Search Asset (Asset No., Name, Model, Serial No., Location)
                </label>
                <input id="search"
                       name="search"
                       type="text"
                       value="{{ $search }}"
                       placeholder="Type Asset No., name, or location..."
                       class="w-full px-4 py-2.5 text-base bg-white border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>

            <div class="md:col-span-3">
                <label for="department" class="block text-sm font-bold text-slate-900 mb-1.5">
                    Department
                </label>
                <select id="department"
                        name="department"
                        class="w-full px-4 py-2.5 text-base bg-white border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept }}" @selected($selectedDepartment === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="status" class="block text-sm font-bold text-slate-900 mb-1.5">
                    Status
                </label>
                <select id="status"
                        name="status"
                        class="w-full px-4 py-2.5 text-base bg-white border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected($selectedStatus === $statusOption)>{{ $statusOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-base cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Find</span>
                </button>
                @if ($search !== '' || $selectedDepartment !== '' || $selectedStatus !== '')
                    <a href="{{ route('assets.index') }}"
                       class="inline-flex items-center justify-center px-3 py-2.5 rounded-lg border-2 border-slate-300 bg-white text-slate-700 font-bold text-sm hover:bg-slate-100">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Print Form + Assets Table -->
    <form id="bulk-print-form" method="GET" action="{{ route('tags.print') }}">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-semibold text-slate-700">
                Showing {{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }} of {{ $assets->total() }} assets
            </p>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-blue-700 bg-white hover:bg-blue-50 text-blue-700 font-bold text-sm transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print Selected Tags</span>
            </button>
        </div>

        <div class="overflow-x-auto border-2 border-slate-200 rounded-xl bg-white">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b-2 border-slate-200 text-slate-900 text-sm font-bold uppercase tracking-wide">
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox"
                                   id="select-all-assets"
                                   aria-label="Select all assets on this page"
                                   onclick="document.querySelectorAll('.asset-checkbox').forEach(cb => cb.checked = this.checked)"
                                   class="w-5 h-5 accent-blue-700 rounded cursor-pointer">
                        </th>
                        <th class="p-4">Asset No.</th>
                        <th class="p-4">Asset Name</th>
                        <th class="p-4">Model / Serial</th>
                        <th class="p-4">Department &amp; Location</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-base">
                    @forelse ($assets as $asset)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 text-center">
                                <input type="checkbox"
                                       name="ids[]"
                                       value="{{ $asset->id }}"
                                       class="asset-checkbox w-5 h-5 accent-blue-700 rounded cursor-pointer">
                            </td>
                            <td class="p-4 font-mono font-bold text-blue-700">
                                <a href="{{ route('assets.show', $asset) }}" class="hover:underline">
                                    {{ $asset->asset_no }}
                                </a>
                            </td>
                            <td class="p-4 font-bold text-slate-900">
                                <a href="{{ route('assets.show', $asset) }}" class="hover:underline">
                                    {{ $asset->asset_name }}
                                </a>
                            </td>
                            <td class="p-4 text-slate-700">
                                <div>{{ $asset->model ?: '—' }}</div>
                                @if ($asset->serial_no)
                                    <div class="text-xs font-mono text-slate-500">SN: {{ $asset->serial_no }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-slate-800">
                                <div class="font-semibold">{{ $asset->location ?: '—' }}</div>
                                @if ($asset->department)
                                    <div class="text-sm text-slate-600">{{ $asset->department }}</div>
                                @endif
                            </td>
                            <td class="p-4">
                                @include('assets.partials.status-badge', ['status' => $asset->status])
                            </td>
                            <td class="p-4 text-right whitespace-nowrap space-x-2">
                                <a href="{{ route('assets.show', $asset) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 font-semibold text-sm">
                                    View
                                </a>
                                <a href="{{ route('assets.edit', $asset) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 font-semibold text-sm">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center text-slate-600">
                                <p class="text-lg font-bold text-slate-800 mb-1">No assets found</p>
                                <p class="text-base mb-4">Click "Add Asset" above to register an item.</p>
                                <a href="{{ route('assets.create') }}"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-700 text-white font-bold text-base">
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
@endsection
