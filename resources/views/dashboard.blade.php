@extends('layouts.app')

@section('content')
    @php
        $totalCount = max(0, (int) ($totalAssets ?? 0));
        $underRepairItems = \App\Models\Asset::where('status', 'Under Repair')->take(3)->get();
        $underRepairCount = (int) ($statusCounts['Under Repair'] ?? $underRepairItems->count());
        $unassignedLocationItems = \App\Models\Asset::query()
            ->where(function ($q) {
                $q->whereNull('location')->orWhere('location', '');
            })
            ->take(3)
            ->get();
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
        subtitle="Hospital property and equipment overview"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('assets.index')">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print Tags</span>
            </x-button>

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

    <!-- Needs Attention Compact Clinical Strip -->
    @if ($hasAttentionItems)
        <div class="p-4 rounded-xl bg-status-warning-bg border border-status-warning-border text-status-warning-text flex flex-wrap items-center justify-between gap-3 shadow-2xs" role="region" aria-label="Items needing attention">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-status-warning-dot shrink-0"></span>
                <span class="text-xs font-bold text-status-warning-text">Needs attention:</span>
                <span class="text-xs text-status-warning-text/90 hidden sm:inline">Items requiring review to keep hospital property records and backups current</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @foreach ($underRepairItems as $item)
                    <a href="{{ route('assets.show', $item) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-status-warning-border text-xs font-semibold text-status-warning-text hover:bg-status-warning-bg/60 shadow-2xs transition-colors"
                       title="{{ $item->asset_name }} ({{ $item->location ?: 'No location' }})">
                        <span class="w-1.5 h-1.5 rounded-full bg-status-warning-dot"></span>
                        <span>{{ $item->asset_no }}: {{ Str::limit($item->asset_name, 18) }} &bull; Under Repair</span>
                        <span class="text-status-warning-dot font-mono text-[11px]">&rarr;</span>
                    </a>
                @endforeach
                @if ($underRepairCount > $underRepairItems->count())
                    <a href="{{ route('assets.index', ['status' => 'Under Repair']) }}"
                       class="text-xs font-semibold text-status-warning-text hover:underline px-1">
                        +{{ $underRepairCount - $underRepairItems->count() }} more under repair &rarr;
                    </a>
                @endif

                @foreach ($unassignedLocationItems as $item)
                    <a href="{{ route('assets.edit', $item) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-status-warning-border text-xs font-semibold text-status-warning-text hover:bg-status-warning-bg/60 shadow-2xs transition-colors"
                       title="{{ $item->asset_name }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-status-warning-dot"></span>
                        <span>{{ $item->asset_no }}: Missing Location</span>
                        <span class="text-status-warning-dot font-mono text-[11px]">&rarr;</span>
                    </a>
                @endforeach

                @if ($backupOverdue)
                    <a href="{{ route('settings.index', ['tab' => 'backup']) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-status-warning-border text-xs font-semibold text-status-warning-text hover:bg-status-warning-bg/60 shadow-2xs transition-colors">
                        <span class="w-1.5 h-1.5 rounded-full bg-status-warning-dot"></span>
                        <span>Database Backup Overdue (&gt;7 days)</span>
                        <span class="text-status-warning-dot font-mono text-[11px]">&rarr;</span>
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="p-3.5 rounded-xl bg-status-success-bg border border-status-success-border text-status-success-text flex items-center justify-between gap-4" role="region" aria-label="System status">
            <div class="flex items-center gap-2.5 text-xs">
                <span class="w-2 h-2 rounded-full bg-status-success-dot shrink-0"></span>
                <span><strong class="font-semibold text-status-success-text">All records current:</strong> No assets under repair, all locations assigned, database backup verified.</span>
            </div>
        </div>
    @endif

    <!-- Summary Cards: Clean hospital metrics with zero filler captions -->
    @php
        $summaryCards = [
            [
                'label' => 'Total Assets',
                'count' => $totalCount,
                'href' => route('assets.index'),
                'dot' => 'bg-slate-900',
            ],
            [
                'label' => 'In Use',
                'count' => (int) ($statusCounts['In Use'] ?? 0),
                'href' => route('assets.index', ['status' => 'In Use']),
                'dot' => 'bg-emerald-600',
            ],
            [
                'label' => 'Under Repair',
                'count' => (int) ($statusCounts['Under Repair'] ?? 0),
                'href' => route('assets.index', ['status' => 'Under Repair']),
                'dot' => 'bg-amber-500',
            ],
            [
                'label' => 'Stored',
                'count' => (int) ($statusCounts['Stored'] ?? 0),
                'href' => route('assets.index', ['status' => 'Stored']),
                'dot' => 'bg-slate-500',
            ],
            [
                'label' => 'Disposed',
                'count' => (int) ($statusCounts['Disposed'] ?? 0),
                'href' => route('assets.index', ['status' => 'Disposed']),
                'dot' => 'bg-neutral-400',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach ($summaryCards as $card)
            <a href="{{ $card['href'] }}"
               class="group p-5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-xs shadow-2xs transition-colors flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full shrink-0 {{ $card['dot'] }}"></span>
                        <p class="text-xs font-semibold text-slate-600 group-hover:text-slate-900 transition-colors">
                            {{ $card['label'] }}
                        </p>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-300 group-hover:text-primary group-hover:translate-x-0.5 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
                <p class="text-3xl font-bold text-slate-900 tabular-nums mt-3 mb-0.5">
                    {{ number_format($card['count']) }}
                </p>
            </a>
        @endforeach
    </div>

    <!-- Main Lower Grid: Department Breakdown + Recently Added Assets -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column: Department Breakdown + Quick Navigation -->
        <div class="lg:col-span-4 space-y-5">
            <x-card>
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Assets by Department</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Inventory distribution across hospital units</p>
                        </div>
                        <a href="{{ route('assets.index') }}" class="text-xs font-medium text-slate-600 hover:text-primary hover:underline">
                            View all &rarr;
                        </a>
                    </div>
                </x-slot:header>

                @if (! empty($departmentCounts) && count($departmentCounts) > 0)
                    <ul class="space-y-3.5">
                        @foreach ($departmentCounts as $deptRow)
                            @php
                                $barWidth = $totalCount > 0 ? max(6, round(($deptRow->total / $totalCount) * 100)) : 0;
                                $isUnassigned = $deptRow->dept_name === 'Unassigned';
                                $barColor = $isUnassigned ? 'bg-slate-300' : ($loop->first ? 'bg-primary' : 'bg-slate-600');
                            @endphp
                            <li>
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    @if (! $isUnassigned)
                                        <a href="{{ route('assets.index', ['department' => $deptRow->dept_name]) }}"
                                           class="font-medium text-slate-800 hover:text-primary hover:underline truncate">
                                            {{ $deptRow->dept_name }}
                                        </a>
                                    @else
                                        <span class="text-slate-500">Unassigned</span>
                                    @endif
                                    <span class="font-mono text-xs font-semibold text-slate-600 tabular-nums">
                                        {{ $deptRow->total }}
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $barWidth }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span class="font-medium">Total Registered Departments</span>
                        <span class="font-mono font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                            {{ count($departmentCounts) }}
                        </span>
                    </div>
                @else
                    <div class="py-6 text-center">
                        <p class="text-xs text-slate-500">No departments recorded yet</p>
                    </div>
                @endif
            </x-card>

            <!-- Quick Operational Shortcuts Card (Balances Column Height) -->
            <x-card padding="p-4">
                <div class="flex items-center justify-between text-xs font-semibold text-slate-800 mb-3">
                    <span>Quick Tasks</span>
                    <span class="text-slate-400 font-normal">Floor actions</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('assets.find') }}"
                       class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-colors text-xs font-medium text-slate-700">
                        <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Find Asset</span>
                    </a>
                    <a href="{{ route('reports.index') }}"
                       class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-colors text-xs font-medium text-slate-700">
                        <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Export Excel</span>
                    </a>
                </div>
            </x-card>
        </div>

        <!-- Right Column: Recently Added Assets Table (No clipping, single-line truncated, icon actions) -->
        <div class="lg:col-span-8">
            <x-card padding="p-0">
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Recently Added Assets</h3>
                            <p class="text-xs text-slate-600 mt-0.5">Latest equipment recorded in the hospital register</p>
                        </div>
                        <a href="{{ route('assets.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 transition-colors shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Add Asset</span>
                        </a>
                    </div>
                </x-slot:header>

                @if (! empty($recentAssets) && count($recentAssets) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50/95 sticky top-0 z-10 border-b border-slate-200 text-xs font-semibold text-slate-700 select-none">
                                <tr>
                                    <th class="py-3 px-4 w-32 whitespace-nowrap">Asset No.</th>
                                    <th class="py-3 px-4">Equipment &amp; Location</th>
                                    <th class="py-3 px-4 w-28 whitespace-nowrap">Status</th>
                                    <th class="py-3 px-4 w-32 min-w-[128px] text-right pr-4 whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 [&>tr]:h-[52px]">
                                @foreach ($recentAssets as $recent)
                                    <tr class="hover:bg-slate-50/90 transition-colors cursor-pointer group"
                                        onclick="window.location.href='{{ route('assets.show', $recent) }}'">
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-mono text-xs font-semibold group-hover:border-slate-300 transition-colors">
                                                {{ $recent->asset_no }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-4 min-w-[200px] max-w-[320px]">
                                            <a href="{{ route('assets.show', $recent) }}"
                                               class="font-semibold text-slate-900 group-hover:text-primary block truncate transition-colors"
                                               title="{{ $recent->asset_name }}">
                                                {{ $recent->asset_name }}
                                            </a>
                                            <p class="text-[11px] text-slate-600 truncate mt-0.5"
                                               title="{{ $recent->location ?: 'No location' }} &bull; {{ $recent->department ?: 'Unassigned' }}">
                                                {{ $recent->location ?: 'No location' }} &bull; {{ $recent->department ?: 'Unassigned' }}
                                            </p>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <x-badge :status="$recent->status" />
                                        </td>
                                        <td class="py-2.5 px-4 w-32 min-w-[128px] text-right pr-4 whitespace-nowrap">
                                            <div class="inline-flex items-center justify-end gap-1" onclick="event.stopPropagation()">
                                                <x-icon-button :href="route('assets.show', $recent)" title="View Asset" size="sm">
                                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </x-icon-button>
                                                <x-icon-button :href="route('tags.print', ['ids' => [$recent->id]])" title="Print Tag" size="sm">
                                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                    </svg>
                                                </x-icon-button>
                                                <x-icon-button :href="route('assets.move.create', $recent)" title="Move Asset" size="sm">
                                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                    </svg>
                                                </x-icon-button>
                                            </div>
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
                            description="Start building the hospital property register by adding your first piece of equipment"
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
