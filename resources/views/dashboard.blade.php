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
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print Tags</span>
            </x-button>

            <x-button variant="secondary" :href="route('scan.index')">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Scan Tag</span>
            </x-button>

            <x-button variant="primary" :href="route('assets.create')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Asset</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Needs Attention Clinical Strip -->
    @if ($hasAttentionItems)
        <div class="p-4 rounded-xl bg-amber-50/90 border border-amber-300 text-amber-950 flex flex-wrap items-center justify-between gap-4" role="region" aria-label="Items needing attention">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-700 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-950">Needs attention</p>
                    <p class="text-xs text-amber-800">Items requiring review to keep hospital property records and backups current</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                @if ($underRepairCount > 0)
                    <a href="{{ route('assets.index', ['status' => 'Under Repair']) }}"
                       class="min-h-[44px] inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-amber-300 text-xs font-medium text-amber-950 hover:bg-amber-100/70 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                        <span>{{ $underRepairCount }} {{ Str::plural('asset', $underRepairCount) }} under repair</span>
                        <span class="text-amber-700">&rarr;</span>
                    </a>
                @endif

                @if ($unassignedLocationCount > 0)
                    <a href="{{ route('assets.index') }}"
                       class="min-h-[44px] inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-amber-300 text-xs font-medium text-amber-950 hover:bg-amber-100/70 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                        <span>{{ $unassignedLocationCount }} without a location</span>
                        <span class="text-amber-700">&rarr;</span>
                    </a>
                @endif

                @if ($backupOverdue)
                    <a href="{{ route('settings.index') }}"
                       class="min-h-[44px] inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-amber-300 text-xs font-medium text-amber-950 hover:bg-amber-100/70 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                        <span>Database backup overdue (&gt;7 days)</span>
                        <span class="text-amber-700">&rarr;</span>
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex items-center justify-between gap-4" role="region" aria-label="System status">
            <div class="flex items-center gap-2.5 text-sm">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><strong class="font-semibold">All clear:</strong> No assets under repair, all items have assigned locations, and database backups are current</span>
            </div>
        </div>
    @endif

    <!-- Summary Cards: Pure typographic stat cards (simple counts, sentence-case labels, no decorative icon boxes) -->
    @php
        $summaryCards = [
            [
                'label' => 'Total Assets',
                'count' => $totalCount,
                'caption' => 'Registered equipment',
                'href' => route('assets.index'),
                'dot' => 'bg-[#b91c1c]',
            ],
            [
                'label' => 'In Use',
                'count' => (int) ($statusCounts['In Use'] ?? 0),
                'caption' => 'Active on hospital floors',
                'href' => route('assets.index', ['status' => 'In Use']),
                'dot' => 'bg-emerald-600',
            ],
            [
                'label' => 'Under Repair',
                'count' => (int) ($statusCounts['Under Repair'] ?? 0),
                'caption' => 'In maintenance or biomed',
                'href' => route('assets.index', ['status' => 'Under Repair']),
                'dot' => 'bg-amber-500',
            ],
            [
                'label' => 'Stored',
                'count' => (int) ($statusCounts['Stored'] ?? 0),
                'caption' => 'Standby in storage room',
                'href' => route('assets.index', ['status' => 'Stored']),
                'dot' => 'bg-slate-500',
            ],
            [
                'label' => 'Disposed',
                'count' => (int) ($statusCounts['Disposed'] ?? 0),
                'caption' => 'Decommissioned items',
                'href' => route('assets.index', ['status' => 'Disposed']),
                'dot' => 'bg-neutral-400',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach ($summaryCards as $card)
            <a href="{{ $card['href'] }}"
               class="group p-5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 shadow-2xs transition-colors flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full shrink-0 {{ $card['dot'] }}"></span>
                        <p class="text-xs font-medium text-slate-600 group-hover:text-slate-900 transition-colors">
                            {{ $card['label'] }}
                        </p>
                    </div>
                    <p class="text-3xl font-semibold text-slate-900 tabular-nums mt-3">
                        {{ number_format($card['count']) }}
                    </p>
                </div>
                <p class="text-xs text-slate-500 mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                    <span>{{ $card['caption'] }}</span>
                    <span class="text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">&rarr;</span>
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
                            <h3 class="text-base font-semibold text-slate-900">Assets by Department</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Select a department to filter inventory</p>
                        </div>
                        <a href="{{ route('assets.index') }}" class="text-xs font-medium text-slate-600 hover:text-slate-900 hover:underline">
                            View all &rarr;
                        </a>
                    </div>
                </x-slot:header>

                @if (! empty($departmentCounts) && count($departmentCounts) > 0)
                    <ul class="space-y-4">
                        @foreach ($departmentCounts as $index => $deptRow)
                            @php
                                $barWidth = $totalCount > 0 ? max(6, round(($deptRow->total / $totalCount) * 100)) : 0;
                                $isUnassigned = $deptRow->dept_name === 'Unassigned';
                                $barColor = $isUnassigned
                                    ? 'bg-slate-400'
                                    : ($index === 0 ? 'bg-[#b91c1c]' : 'bg-slate-700');
                            @endphp
                            <li>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    @if (! $isUnassigned)
                                        <a href="{{ route('assets.index', ['department' => $deptRow->dept_name]) }}"
                                           class="font-medium text-slate-800 hover:text-[#b91c1c] hover:underline truncate">
                                            {{ $deptRow->dept_name }}
                                        </a>
                                    @else
                                        <span class="text-slate-500">Unassigned</span>
                                    @endif
                                    <span class="font-mono text-xs font-medium text-slate-600 tabular-nums">
                                        {{ $deptRow->total }}
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $barWidth }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="py-8 text-center">
                        <p class="text-sm text-slate-500">No departments recorded yet</p>
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
                            <h3 class="text-base font-semibold text-slate-900">Recently Added Assets</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Latest equipment recorded in the hospital register</p>
                        </div>
                        <a href="{{ route('assets.create') }}" class="text-xs font-medium text-[#b91c1c] hover:text-[#991b1b] hover:underline">
                            + Add Asset
                        </a>
                    </div>
                </x-slot:header>

                @if (! empty($recentAssets) && count($recentAssets) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-[15px]">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                                <tr>
                                    <th class="py-3 px-4">Asset No.</th>
                                    <th class="py-3 px-4">Asset Name</th>
                                    <th class="py-3 px-4">Department &amp; Location</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 [&>tr:hover]:bg-slate-50/90 transition-colors">
                                @foreach ($recentAssets as $recent)
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <a href="{{ route('assets.show', $recent) }}"
                                               class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-mono text-xs font-medium hover:border-slate-300 hover:text-[#b91c1c] transition-colors">
                                                {{ $recent->asset_no }}
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 font-medium text-slate-900">
                                            <a href="{{ route('assets.show', $recent) }}" class="hover:text-[#b91c1c] hover:underline">
                                                {{ $recent->asset_name }}
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-slate-600">
                                            <div class="text-slate-800">{{ $recent->location ?: '—' }}</div>
                                            @if ($recent->department)
                                                <div class="text-xs text-slate-500">{{ $recent->department }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <x-badge :status="$recent->status" />
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <a href="{{ route('tags.print', ['ids' => [$recent->id]]) }}"
                                               class="min-h-[44px] inline-flex items-center px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
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
