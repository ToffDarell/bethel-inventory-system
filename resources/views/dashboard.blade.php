@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold tracking-tight text-red-600">Dashboard</h2>
            <p class="text-base text-neutral-600 mt-1">
                {{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }} &mdash; Property Asset Overview
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('scan.index') }}"
               class="inline-flex items-center gap-2.5 px-5 py-3 rounded-xl border-2 border-red-600 bg-white hover:bg-red-50 active:translate-y-[1px] text-red-600 font-bold text-base transition">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Scan Tag</span>
            </a>

            <a href="{{ route('assets.create') }}"
               class="inline-flex items-center gap-2.5 px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-base shadow-xs transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Asset</span>
            </a>
        </div>
    </div>

    @php
        $totalCount = max(0, (int) ($totalAssets ?? 0));
    @endphp

    <!-- Summary Cards: Total + Count by Status (White & Red Palette) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <a href="{{ route('assets.index') }}"
           class="p-5 rounded-2xl border-2 border-red-600 bg-red-600 text-white shadow-xs hover:bg-red-700 transition block">
            <div class="flex items-center justify-between">
                <p class="text-xs font-extrabold uppercase tracking-wider text-white">Total Assets</p>
                <span class="w-8 h-8 rounded-lg bg-white text-red-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
            </div>
            <p class="text-4xl font-extrabold tabular-nums mt-3 text-white">{{ $totalCount }}</p>
            <p class="text-xs text-white mt-2 font-bold underline">View complete inventory &rarr;</p>
        </a>

        @foreach (\App\Models\Asset::STATUSES as $status)
            @php
                $count = (int) ($statusCounts[$status] ?? 0);
                $pct = $totalCount > 0 ? round(($count / $totalCount) * 100) : 0;
            @endphp
            <a href="{{ route('assets.index', ['status' => $status]) }}"
               class="p-5 rounded-2xl border-2 border-red-100 bg-white hover:border-red-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-neutral-600">{{ $status }}</p>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border bg-red-50 text-red-700 border-red-200 tabular-nums">
                            {{ $pct }}%
                        </span>
                    </div>
                    <p class="text-3xl font-extrabold text-red-600 tabular-nums mt-2.5">{{ $count }}</p>
                </div>

                <div class="mt-4 w-full h-1.5 rounded-full bg-red-100 overflow-hidden">
                    <div class="h-full rounded-full bg-red-600" style="width: {{ $pct }}%"></div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Count by Department -->
        <div class="lg:col-span-5 p-6 bg-white border-2 border-red-100 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-5 pb-3.5 border-b border-red-100">
                <div>
                    <h3 class="text-xl font-extrabold text-neutral-900">Assets by Department</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Click a department to filter assets</p>
                </div>
                <a href="{{ route('assets.index') }}" class="text-sm font-bold text-red-600 hover:underline">View All &rarr;</a>
            </div>

            @if (! empty($departmentCounts) && count($departmentCounts) > 0)
                <ul class="space-y-3.5">
                    @foreach ($departmentCounts as $deptRow)
                        @php
                            $deptPct = $totalCount > 0 ? max(4, round(($deptRow->total / $totalCount) * 100)) : 0;
                        @endphp
                        <li>
                            <div class="flex items-center justify-between text-base mb-1.5">
                                @if ($deptRow->dept_name !== 'Unassigned')
                                    <a href="{{ route('assets.index', ['department' => $deptRow->dept_name]) }}"
                                       class="font-bold text-neutral-900 hover:text-red-600 hover:underline truncate">
                                        {{ $deptRow->dept_name }}
                                    </a>
                                @else
                                    <span class="text-neutral-500 italic font-medium">Unassigned</span>
                                @endif
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-red-600 text-white tabular-nums">
                                    {{ $deptRow->total }}
                                </span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-red-100 overflow-hidden">
                                <div class="h-full rounded-full bg-red-600" style="width: {{ $deptPct }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="py-10 text-center">
                    <p class="text-base text-neutral-600">No assets registered yet.</p>
                </div>
            @endif
        </div>

        <!-- Recently Added Assets -->
        <div class="lg:col-span-7 p-6 bg-white border-2 border-red-100 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-5 pb-3.5 border-b border-red-100">
                <div>
                    <h3 class="text-xl font-extrabold text-neutral-900">Recently Added Assets</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Latest equipment recorded in the register</p>
                </div>
                <a href="{{ route('assets.create') }}" class="text-sm font-bold text-red-600 hover:underline">+ Add Asset</a>
            </div>

            @if (! empty($recentAssets) && count($recentAssets) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-red-600 text-white text-xs font-extrabold uppercase tracking-wider">
                                <th class="p-3 rounded-tl-lg">Asset No.</th>
                                <th class="p-3">Asset Name</th>
                                <th class="p-3">Department / Location</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right rounded-tr-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-100 text-base">
                            @foreach ($recentAssets as $recent)
                                <tr class="hover:bg-red-50 transition">
                                    <td class="p-3 whitespace-nowrap">
                                        <a href="{{ route('assets.show', $recent) }}"
                                           class="inline-flex items-center px-2.5 py-1 rounded-md bg-red-50 text-red-700 border border-red-200 font-mono text-sm font-bold hover:bg-red-100 transition">
                                            {{ $recent->asset_no }}
                                        </a>
                                    </td>
                                    <td class="p-3 font-bold text-neutral-900">
                                        <a href="{{ route('assets.show', $recent) }}" class="hover:text-red-600 hover:underline">
                                            {{ $recent->asset_name }}
                                        </a>
                                    </td>
                                    <td class="p-3 text-sm text-neutral-700">
                                        <div class="font-bold text-neutral-900">{{ $recent->location ?: '—' }}</div>
                                        @if ($recent->department)
                                            <div class="text-xs text-neutral-500">{{ $recent->department }}</div>
                                        @endif
                                    </td>
                                    <td class="p-3 whitespace-nowrap">
                                        @include('assets.partials.status-badge', ['status' => $recent->status])
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        <a href="{{ route('tags.print', ['ids' => [$recent->id]]) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-600 bg-white hover:bg-red-600 hover:text-white text-xs font-bold text-red-600 transition">
                                            Print Tag
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 text-center">
                    <p class="text-base text-neutral-600 mb-4">No assets have been added yet.</p>
                    <a href="{{ route('assets.create') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-600 text-white font-bold text-sm hover:bg-red-700">
                        <span>Add Your First Asset</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
