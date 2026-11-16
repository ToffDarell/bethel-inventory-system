@extends('layouts.app')

@section('content')
    @php
        $totalCount = max(0, (int) ($totalAssets ?? 0));
        $hasAttentionItems = ($underRepairCount ?? 0) > 0
            || ($damagedCount ?? 0) > 0
            || ($unassignedLocationCount ?? 0) > 0
            || (($reprintNeededCount ?? 0) > 0)
            || (($noCategoryCount ?? 0) > 0)
            || ($backupOverdue ?? false)
            || (($belowMinCostCount ?? 0) > 0 && ($isAdmin ?? false));
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

    @if (session('admin_recovery_code_once'))
        <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 shadow-2xs" role="region" aria-label="One-time administrator recovery code">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-600 shrink-0"></span>
                        <h2 class="text-sm font-bold text-amber-950">Save your Administrator Recovery Code now</h2>
                    </div>
                    <p class="text-xs text-amber-900">
                        This code is shown only once. If you forget your password, you can reset it offline using this code.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <code id="once-recovery-code" class="px-3 py-1.5 rounded-lg bg-white border border-amber-300 font-mono text-sm font-bold tracking-wider text-slate-900 select-all">{{ session('admin_recovery_code_once') }}</code>
                    <button type="button"
                            onclick="navigator.clipboard?.writeText('{{ session('admin_recovery_code_once') }}'); this.textContent='Copied';"
                            class="px-3 py-1.5 rounded-lg bg-amber-900 hover:bg-amber-950 text-white text-xs font-semibold cursor-pointer">
                        Copy
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if (($isAdmin ?? false) && ! ($checklistDismissed ?? false) && ! empty($firstRunChecklist))
        <section class="mb-4 p-4 rounded-xl bg-white border border-slate-200 shadow-2xs" aria-label="Initial Setup Checklist">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-sm font-bold text-slate-900">Initial Setup Checklist</h2>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 tabular-nums">
                        {{ $checklistCompleted }} of {{ count($firstRunChecklist) }} completed
                    </span>
                </div>
                <form method="POST" action="{{ route('dashboard.dismiss-checklist') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-slate-800 underline cursor-pointer">
                        Dismiss checklist
                    </button>
                </form>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach ($firstRunChecklist as $step)
                    <a href="{{ $step['url'] }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg border text-xs font-medium transition-colors {{ $step['done'] ? 'bg-emerald-50/60 border-emerald-200 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-100/70' }}">
                        @if ($step['done'])
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="w-4 h-4 rounded-full border-2 border-slate-300 shrink-0"></span>
                        @endif
                        <span class="{{ $step['done'] ? 'line-through text-emerald-800/80' : '' }}">{{ $step['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Needs Attention Compact Clinical Strip -->
    @if ($hasAttentionItems)
        <div class="p-4 rounded-xl bg-status-warning-bg border border-status-warning-border text-status-warning-text flex flex-wrap items-center justify-between gap-3 shadow-2xs" role="region" aria-label="Items needing attention">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-status-warning-dot shrink-0"></span>
                <span class="text-xs font-bold text-status-warning-text">Needs attention:</span>
                <span class="text-xs text-status-warning-text/90 hidden sm:inline">Items requiring review to keep hospital property records and backups current</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- 1. Under Repair Link --}}
                @if (($underRepairCount ?? 0) > 0)
                    <a href="{{ route('assets.index', ['status' => 'Under Repair']) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-status-warning-border text-xs font-semibold text-status-warning-text hover:bg-status-warning-bg/60 shadow-2xs transition-colors"
                       title="View {{ $underRepairCount }} {{ \Illuminate\Support\Str::plural('asset', $underRepairCount) }} under repair in assets list">
                        <span class="w-1.5 h-1.5 rounded-full bg-status-warning-dot"></span>
                        <span>{{ $underRepairCount }} {{ \Illuminate\Support\Str::plural('asset', $underRepairCount) }} under repair</span>
                        <span class="text-status-warning-dot font-mono text-[11px]">&rarr;</span>
                    </a>
                @endif

                {{-- 2. Damage Condition Link --}}
                @if (($damagedCount ?? 0) > 0)
                    <a href="{{ route('assets.index', ['condition' => 'Damage']) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-rose-200 text-xs font-semibold text-rose-700 hover:bg-rose-50 shadow-2xs transition-colors"
                       title="View {{ $damagedCount }} {{ \Illuminate\Support\Str::plural('asset', $damagedCount) }} in Damage condition in assets list">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span>{{ $damagedCount }} {{ \Illuminate\Support\Str::plural('asset', $damagedCount) }} in Damage condition</span>
                        <span class="text-rose-500 font-mono text-[11px]">&rarr;</span>
                    </a>
                @endif

                {{-- 3. No Location Link --}}
                @if (($unassignedLocationCount ?? 0) > 0)
                    <a href="{{ route('assets.index', ['location' => 'unassigned']) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-status-warning-border text-xs font-semibold text-status-warning-text hover:bg-status-warning-bg/60 shadow-2xs transition-colors"
                       title="View {{ $unassignedLocationCount }} {{ \Illuminate\Support\Str::plural('asset', $unassignedLocationCount) }} without recorded location in assets list">
                        <span class="w-1.5 h-1.5 rounded-full bg-status-warning-dot"></span>
                        <span>{{ $unassignedLocationCount }} {{ \Illuminate\Support\Str::plural('asset', $unassignedLocationCount) }} without location</span>
                        <span class="text-status-warning-dot font-mono text-[11px]">&rarr;</span>
                    </a>
                @endif

                {{-- Tags Need Reprinting Link --}}
                @if (($reprintNeededCount ?? 0) > 0)
                    <a href="{{ route('assets.index', ['needs_reprint' => 1]) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-sky-300 text-xs font-semibold text-primary hover:bg-sky-50 shadow-2xs transition-colors"
                       title="View {{ $reprintNeededCount }} {{ \Illuminate\Support\Str::plural('tag', $reprintNeededCount) }} needing reprinting">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        <span>{{ $reprintNeededCount }} {{ \Illuminate\Support\Str::plural('tag', $reprintNeededCount) }} {{ (int) $reprintNeededCount === 1 ? 'needs' : 'need' }} reprinting</span>
                        <span class="text-primary font-mono text-[11px]">&rarr;</span>
                    </a>
                @endif

                {{-- 4. Database Backup Overdue Link --}}
                @if ($backupOverdue ?? false)
                    <a href="{{ route('settings.index', ['tab' => 'backup']) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-status-warning-border text-xs font-semibold text-status-warning-text hover:bg-status-warning-bg/60 shadow-2xs transition-colors"
                       title="Review and create database backups">
                        <span class="w-1.5 h-1.5 rounded-full bg-status-warning-dot"></span>
                        <span>Database backup overdue (&gt;7 days)</span>
                        <span class="text-status-warning-dot font-mono text-[11px]">&rarr;</span>
                    </a>
                @endif

                {{-- 5. Below Minimum Cost Link (Admin only) --}}
                @if (($isAdmin ?? false) && ($belowMinCostCount ?? 0) > 0)
                    <a href="{{ route('assets.index', ['below_min_cost' => 1]) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-amber-300 text-xs font-semibold text-amber-900 hover:bg-amber-100/70 shadow-2xs transition-colors"
                       title="View {{ $belowMinCostCount }} {{ \Illuminate\Support\Str::plural('asset', $belowMinCostCount) }} below minimum depreciation threshold">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>{{ $belowMinCostCount }} {{ \Illuminate\Support\Str::plural('asset', $belowMinCostCount) }} below minimum cost</span>
                        <span class="text-amber-700 font-mono text-[11px]">&rarr;</span>
                    </a>
                @endif

                {{-- 6. Assets without a category Link --}}
                @if (($noCategoryCount ?? 0) > 0)
                    <a href="{{ route('assets.index', ['category' => 'none']) }}"
                       class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-amber-300 text-xs font-semibold text-amber-900 hover:bg-amber-100/70 shadow-2xs transition-colors"
                       title="View {{ $noCategoryCount }} {{ \Illuminate\Support\Str::plural('asset', $noCategoryCount) }} without a category">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>{{ $noCategoryCount }} {{ \Illuminate\Support\Str::plural('asset', $noCategoryCount) }} without a category</span>
                        <span class="text-amber-700 font-mono text-[11px]">&rarr;</span>
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

    <!-- Main Operational Table: Recently Added Assets (Full Width) -->
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
                            <th class="py-3 px-4 w-36 whitespace-nowrap">Asset No.</th>
                            <th class="py-3 px-4">Equipment &amp; Location</th>
                            <th class="py-3 px-4 w-32 whitespace-nowrap">Status</th>
                            <th class="py-3 px-4 w-36 min-w-[140px] text-right pr-4 whitespace-nowrap">Actions</th>
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
                                <td class="py-2.5 px-4">
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
                                <td class="py-2.5 px-4 w-36 min-w-[140px] text-right pr-4 whitespace-nowrap">
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

    <!-- Side-by-Side Lower Section: Department Breakdown + Quick Tasks (Left) and Category Breakdown (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <!-- Left Column: Department Breakdown + Quick Tasks -->
        <div class="space-y-6">
            <!-- Assets by Department -->
            <x-card>
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Assets by Department</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Equipment count in each department</p>
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

            <!-- Quick Tasks Card -->
            <x-card>
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Quick Tasks</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Common shortcuts and tools</p>
                        </div>
                        <span class="text-xs text-slate-400">Inventory tools</span>
                    </div>
                </x-slot:header>
                <div class="grid grid-cols-2 gap-2.5">
                    <x-button variant="secondary" :href="route('assets.find')" class="w-full text-xs justify-center py-2.5">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Find Asset
                    </x-button>
                    <x-button variant="secondary" :href="route('reports.index')" class="w-full text-xs justify-center py-2.5">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Reports Hub
                    </x-button>
                    <x-button variant="secondary" :href="route('tags.print')" class="w-full text-xs justify-center py-2.5">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print Tags
                    </x-button>
                    <x-button variant="secondary" :href="route('assets.create')" class="w-full text-xs justify-center py-2.5">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Asset
                    </x-button>
                </div>
            </x-card>
        </div>

        <!-- Right Column: By Category Breakdown -->
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">By Category</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Equipment count in each category</p>
                    </div>
                    <a href="{{ route('assets.index') }}" class="text-xs font-medium text-slate-600 hover:text-primary hover:underline">
                        Filter &rarr;
                    </a>
                </div>
            </x-slot:header>

            <div class="divide-y divide-slate-100">
                @forelse ($categoryBreakdown as $catItem)
                    <a href="{{ $catItem['id'] ? route('assets.index', ['category' => $catItem['id']]) : route('assets.index') }}"
                       class="py-2.5 px-2 flex items-center justify-between hover:bg-slate-50 transition-colors group rounded-md">
                        <div>
                            <span class="text-xs font-semibold text-slate-800 group-hover:text-primary block">{{ $catItem['name'] }}</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                            {{ $catItem['count'] }}
                        </span>
                    </a>
                @empty
                    <p class="text-xs text-slate-500 py-3 text-center">No categories configured yet</p>
                @endforelse
            </div>

            <div class="mt-4 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span class="font-medium">Configured Categories</span>
                <span class="font-mono font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                    {{ count($categoryBreakdown) }}
                </span>
            </div>
        </x-card>
    </div>
@endsection
