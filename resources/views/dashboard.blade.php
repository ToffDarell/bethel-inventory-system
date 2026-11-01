@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">Dashboard</h2>
            <p class="text-base text-slate-600 mt-1">
                {{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }} &mdash; Property Asset Overview
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('scan.index') }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-lg border-2 border-blue-700 bg-white hover:bg-blue-50 text-blue-700 font-bold text-base transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Scan Tag</span>
            </a>

            <a href="{{ route('assets.create') }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-base shadow-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Asset</span>
            </a>
        </div>
    </div>

    <!-- Summary Cards: Total + Count by Status -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <a href="{{ route('assets.index') }}" class="p-5 rounded-xl border-2 border-blue-200 bg-blue-50/50 hover:border-blue-400 transition block">
            <p class="text-sm font-bold uppercase tracking-wide text-blue-900">Total Assets</p>
            <p class="text-4xl font-extrabold text-blue-700 mt-2">{{ $totalAssets ?? 0 }}</p>
        </a>

        @foreach (\App\Models\Asset::STATUSES as $status)
            <a href="{{ route('assets.index', ['status' => $status]) }}" class="p-5 rounded-xl border-2 border-slate-200 bg-white hover:border-blue-300 transition block">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold uppercase tracking-wide text-slate-600">{{ $status }}</p>
                </div>
                <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $statusCounts[$status] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Count by Department -->
        <div class="lg:col-span-5 p-6 bg-white border-2 border-slate-200 rounded-xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
                <h3 class="text-xl font-bold text-slate-900">Assets by Department</h3>
                <a href="{{ route('assets.index') }}" class="text-sm font-bold text-blue-700 hover:underline">View All &rarr;</a>
            </div>

            @if (! empty($departmentCounts) && count($departmentCounts) > 0)
                <ul class="divide-y divide-slate-200">
                    @foreach ($departmentCounts as $deptRow)
                        <li class="py-3 flex items-center justify-between text-base">
                            @if ($deptRow->dept_name !== 'Unassigned')
                                <a href="{{ route('assets.index', ['department' => $deptRow->dept_name]) }}"
                                   class="font-semibold text-slate-900 hover:text-blue-700 hover:underline">
                                    {{ $deptRow->dept_name }}
                                </a>
                            @else
                                <span class="text-slate-500 italic">Unassigned</span>
                            @endif
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-slate-100 text-slate-900">
                                {{ $deptRow->total }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-base text-slate-600 py-6 text-center">
                    No assets registered yet.
                </p>
            @endif
        </div>

        <!-- Recently Added Assets -->
        <div class="lg:col-span-7 p-6 bg-white border-2 border-slate-200 rounded-xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
                <h3 class="text-xl font-bold text-slate-900">Recently Added Assets</h3>
                <a href="{{ route('assets.create') }}" class="text-sm font-bold text-blue-700 hover:underline">+ Add Asset</a>
            </div>

            @if (! empty($recentAssets) && count($recentAssets) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-xs font-bold uppercase tracking-wide text-slate-700">
                                <th class="p-3">Asset No.</th>
                                <th class="p-3">Asset Name</th>
                                <th class="p-3">Department / Location</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-base">
                            @foreach ($recentAssets as $recent)
                                <tr class="hover:bg-slate-50">
                                    <td class="p-3 font-mono font-bold text-blue-700 whitespace-nowrap">
                                        <a href="{{ route('assets.show', $recent) }}" class="hover:underline">
                                            {{ $recent->asset_no }}
                                        </a>
                                    </td>
                                    <td class="p-3 font-semibold text-slate-900">{{ $recent->asset_name }}</td>
                                    <td class="p-3 text-sm text-slate-700">
                                        <div>{{ $recent->location ?: '—' }}</div>
                                        @if ($recent->department)
                                            <div class="text-xs text-slate-500">{{ $recent->department }}</div>
                                        @endif
                                    </td>
                                    <td class="p-3 whitespace-nowrap">
                                        @include('assets.partials.status-badge', ['status' => $recent->status])
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        <a href="{{ route('tags.print', ['ids' => [$recent->id]]) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-xs font-bold text-slate-800">
                                            Print Tag
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center">
                    <p class="text-base text-slate-600 mb-4">No assets have been added yet.</p>
                    <a href="{{ route('assets.create') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-700 text-white font-bold text-sm hover:bg-blue-800">
                        <span>Add Your First Asset</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
