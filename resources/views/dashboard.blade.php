@extends('layouts.app')

@section('content')
    @php
        $totalCount = max(0, (int) ($totalAssets ?? 0));
        $underRepairCount = (int) ($statusCounts['Under Repair'] ?? 0);
        $unassignedLocationCount = \App\Models\Asset::query()
            ->where(function ($q) {
                $q->whereNull('location')->orWhere('location', '');
            })
            ->count();
        $backupOverdue = app(\App\Services\BackupService::class)->isBackupOverdue(7);
        $hasAttentionItems = $underRepairCount > 0 || $backupOverdue || $unassignedLocationCount > 0;
    @endphp

    <!-- Page Header with Quick Actions -->
    <x-page-header
        title="Dashboard"
        :subtitle="\App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') . ' — Property Asset Overview'"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('assets.index')">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print Tags</span>
            </x-button>

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

    <!-- Needs Attention Clinical Strip -->
    @if ($hasAttentionItems)
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 flex flex-wrap items-center justify-between gap-4 shadow-2xs" role="region" aria-label="Items needing attention">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-100 border border-amber-300 flex items-center justify-center text-amber-800 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-950">Needs Attention</p>
                    <p class="text-xs text-amber-800">Review the items below to keep hospital property records and backups current.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                @if ($underRepairCount > 0)
                    <a href="{{ route('assets.index', ['status' => 'Under Repair']) }}"
                       class="min-h-[40px] inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-white border border-amber-300 text-xs font-semibold text-amber-900 hover:bg-amber-100/70 transition">
                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                        <span>{{ $underRepairCount }} {{ Str::plural('asset', $underRepairCount) }} under repair</span>
                        <span class="text-amber-700">&rarr;</span>
                    </a>
                @endif

                @if ($unassignedLocationCount > 0)
                    <a href="{{ route('assets.index') }}"
                       class="min-h-[40px] inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-white border border-amber-300 text-xs font-semibold text-amber-900 hover:bg-amber-100/70 transition">
                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                        <span>{{ $unassignedLocationCount }} without a location</span>
                        <span class="text-amber-700">&rarr;</span>
                    </a>
                @endif

                @if ($backupOverdue)
                    <a href="{{ route('settings.index') }}"
                       class="min-h-[40px] inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-white border border-amber-300 text-xs font-semibold text-amber-900 hover:bg-amber-100/70 transition">
                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                        <span>Database backup overdue (&gt;7 days)</span>
                        <span class="text-amber-700">&rarr;</span>
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex items-center justify-between gap-4 shadow-2xs" role="region" aria-label="System status">
            <div class="flex items-center gap-2.5 text-sm font-medium">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><strong>All Clear:</strong> No assets under repair, all items have assigned locations, and database backups are up to date.</span>
            </div>
        </div>
    @endif

    <!-- Summary Cards: Total + Count by Status (Simple counts, no percentages) -->
    @php
        $summaryCards = [
            [
                'label' => 'Total Assets',
                'count' => $totalCount,
                'caption' => 'Registered equipment',
                'href' => route('assets.index'),
                'icon_bg' => 'bg-sky-50 text-[#0f5d8c] border-sky-200',
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            ],
            [
                'label' => 'In Use',
                'count' => (int) ($statusCounts['In Use'] ?? 0),
                'caption' => 'Active on hospital floors',
                'href' => route('assets.index', ['status' => 'In Use']),
                'icon_bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Under Repair',
                'count' => (int) ($statusCounts['Under Repair'] ?? 0),
                'caption' => 'In maintenance / biomed',
                'href' => route('assets.index', ['status' => 'Under Repair']),
                'icon_bg' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            ],
            [
                'label' => 'Stored',
                'count' => (int) ($statusCounts['Stored'] ?? 0),
                'caption' => 'Standby in storage room',
                'href' => route('assets.index', ['status' => 'Stored']),
                'icon_bg' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
            ],
            [
                'label' => 'Disposed',
                'count' => (int) ($statusCounts['Disposed'] ?? 0),
                'caption' => 'Decommissioned items',
                'href' => route('assets.index', ['status' => 'Disposed']),
                'icon_bg' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach ($summaryCards as $card)
            <a href="{{ $card['href'] }}"
               class="group p-5 rounded-xl border border-slate-200 bg-white hover:border-[#0f5d8c] shadow-xs hover:shadow-sm transition flex flex-col justify-between">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-[#0f5d8c] transition-colors">
                            {{ $card['label'] }}
                        </p>
                        <p class="text-3xl font-bold text-slate-900 tabular-nums mt-2">
                            {{ number_format($card['count']) }}
                        </p>
                    </div>
                    <span class="w-9 h-9 rounded-lg border flex items-center justify-center shrink-0 {{ $card['icon_bg'] }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/>
                        </svg>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                    <span>{{ $card['caption'] }}</span>
                    <span class="text-[#0f5d8c] font-semibold opacity-0 group-hover:opacity-100 transition-opacity">&rarr;</span>
                </p>
            </a>
        @endforeach
    </div>

    <!-- Main Lower Grid: Department Breakdown + Recently Added Assets -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Assets by Department -->
        <div class="lg:col-span-4">
            <x-card>
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Assets by Department</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Select a department to filter assets</p>
                        </div>
                        <a href="{{ route('assets.index') }}" class="text-xs font-semibold text-[#0f5d8c] hover:text-[#0c4a6e] hover:underline">
                            View All &rarr;
                        </a>
                    </div>
                </x-slot:header>

                @if (! empty($departmentCounts) && count($departmentCounts) > 0)
                    <ul class="space-y-3.5">
                        @foreach ($departmentCounts as $deptRow)
                            @php
                                $barWidth = $totalCount > 0 ? max(6, round(($deptRow->total / $totalCount) * 100)) : 0;
                            @endphp
                            <li>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    @if ($deptRow->dept_name !== 'Unassigned')
                                        <a href="{{ route('assets.index', ['department' => $deptRow->dept_name]) }}"
                                           class="font-semibold text-slate-800 hover:text-[#0f5d8c] hover:underline truncate">
                                            {{ $deptRow->dept_name }}
                                        </a>
                                    @else
                                        <span class="text-slate-500 italic">Unassigned</span>
                                    @endif
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 tabular-nums">
                                        {{ $deptRow->total }}
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-[#0f5d8c]" style="width: {{ $barWidth }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="py-8 text-center">
                        <p class="text-sm text-slate-500">No departments recorded yet.</p>
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Recently Added Assets -->
        <div class="lg:col-span-8">
            <x-card padding="p-0">
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Recently Added Assets</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Latest equipment recorded in the hospital register</p>
                        </div>
                        <a href="{{ route('assets.create') }}" class="text-xs font-semibold text-[#0f5d8c] hover:text-[#0c4a6e] hover:underline">
                            + Add Asset
                        </a>
                    </div>
                </x-slot:header>

                @if (! empty($recentAssets) && count($recentAssets) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-[15px]">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-600">
                                <tr>
                                    <th class="py-3 px-4">Asset No.</th>
                                    <th class="py-3 px-4">Asset Name</th>
                                    <th class="py-3 px-4">Department / Location</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 [&>tr:hover]:bg-sky-50/40 transition-colors">
                                @foreach ($recentAssets as $recent)
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <a href="{{ route('assets.show', $recent) }}"
                                               class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-[#0f5d8c] border border-slate-200 font-mono text-xs font-bold hover:bg-sky-50 hover:border-sky-200 transition">
                                                {{ $recent->asset_no }}
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-slate-900">
                                            <a href="{{ route('assets.show', $recent) }}" class="hover:text-[#0f5d8c] hover:underline">
                                                {{ $recent->asset_name }}
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-slate-600">
                                            <div class="font-medium text-slate-800">{{ $recent->location ?: '—' }}</div>
                                            @if ($recent->department)
                                                <div class="text-xs text-slate-500">{{ $recent->department }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <x-badge :status="$recent->status" />
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <a href="{{ route('tags.print', ['ids' => [$recent->id]]) }}"
                                               class="min-h-[44px] inline-flex items-center px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                                                Print Tag
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-6">
                        <x-empty-state
                            title="No assets registered yet"
                            description="Start building the hospital property register by adding your first piece of equipment."
                        >
                            <x-slot:action>
                                <x-button variant="primary" :href="route('assets.create')">
                                    Add Your First Asset
                                </x-button>
                            </x-slot:action>
                        </x-empty-state>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
@endsection
