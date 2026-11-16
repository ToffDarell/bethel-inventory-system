@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <x-page-header
            title="Activity Log"
            subtitle="Tamper-evident audit history of asset modifications, location transfers, Excel imports, and system backups."
            :backUrl="route('settings.index')"
            backLabel="Back to Settings"
        >
            <x-slot:actions>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-xs font-medium text-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Audit Trail Active</span>
                </div>
            </x-slot:actions>
        </x-page-header>

        <!-- Filter Chips & Search Bar -->
        <x-card padding="p-4">
            <div class="space-y-3.5">
                <!-- Filter Chips -->
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $chipFilters = [
                            'all' => 'All',
                            'archive' => 'Archived and restored',
                            'location' => 'Location moves',
                            'depreciation' => 'Depreciation policy changes',
                            'backup' => 'Backups and restores',
                            'login' => 'Logins',
                        ];
                    @endphp
                    @foreach ($chipFilters as $filterKey => $filterTitle)
                        @php
                            $isActive = ($currentFilter ?? 'all') === $filterKey;
                        @endphp
                        <a href="{{ route('settings.activity-log', array_filter(['filter' => $filterKey !== 'all' ? $filterKey : null, 'search' => request('search')])) }}"
                           class="min-h-[36px] inline-flex items-center px-3.5 py-1.5 rounded-full text-xs transition-colors cursor-pointer {{ $isActive ? 'bg-slate-900 text-white font-semibold shadow-2xs' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 hover:text-slate-900 font-medium' }}">
                            <span>{{ $filterTitle }}</span>
                        </a>
                    @endforeach
                </div>

                <!-- Search Input & Results Summary -->
                <div class="flex flex-wrap items-center justify-between gap-4 pt-1 border-t border-slate-100">
                    <form method="GET" action="{{ route('settings.activity-log') }}" class="relative flex-1 min-w-[280px] max-w-md">
                        @if (!empty($currentFilter) && $currentFilter !== 'all')
                            <input type="hidden" name="filter" value="{{ $currentFilter }}">
                        @endif
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <input type="text"
                               name="search"
                               id="activity-search"
                               value="{{ $search ?? '' }}"
                               placeholder="Filter by user, action, or asset number..."
                               oninput="filterActivities(this.value)"
                               class="w-full min-h-[44px] pl-10 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    </form>

                    <div class="flex items-center gap-2 text-xs text-slate-500 tabular-nums">
                        <span>Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} of {{ $activities->total() }} audit events</span>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Activity Table -->
        <x-card padding="p-0">
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="py-3 px-4">Date &amp; Time</th>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Activity Description</th>
                        <th class="py-3 px-4">Asset Reference</th>
                        <th class="py-3 px-4">Audit Details &amp; Changes</th>
                    </tr>
                </x-slot:header>

                @forelse ($activities as $activity)
                    @php
                        $subject = $activity->subject;
                        $attributes = $activity->properties['attributes'] ?? [];
                        $isAsset = $subject && isset($subject->asset_no);
                        $changes = \App\Support\ActivityChangeFormatter::format($activity, auth()->user()?->isAdmin() ?? false);
                    @endphp
                    <tr class="activity-row hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 whitespace-nowrap text-xs text-slate-700">
                            <div class="font-medium tabular-nums">{{ $activity->created_at?->format('M d, Y') }}</div>
                            <div class="text-[11px] text-slate-500 tabular-nums mt-0.5">
                                {{ $activity->created_at?->format('h:i:s A') }}
                            </div>
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-xs font-semibold flex items-center justify-center shrink-0">
                                    {{ substr($activity->causer?->name ?? 'S', 0, 1) }}
                                </span>
                                <div>
                                    <div class="text-sm font-semibold text-slate-900 leading-tight">
                                        {{ $activity->causer?->name ?? 'System' }}
                                    </div>
                                    @if ($activity->causer?->username)
                                        <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                            {{ '@' . $activity->causer->username }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="py-3 px-4 text-sm font-medium text-slate-900">
                            {{ $activity->description }}
                        </td>

                        <td class="py-3 px-4">
                            @if ($isAsset)
                                @if (method_exists($subject, 'trashed') && ! $subject->trashed())
                                    <a href="{{ route('assets.show', $subject) }}" class="inline-flex items-center gap-1 font-mono font-semibold text-xs text-primary hover:underline">
                                        <span>{{ $subject->asset_no }}</span>
                                    </a>
                                @else
                                    <span class="font-mono font-semibold text-xs text-slate-700">{{ $subject->asset_no }}</span>
                                @endif
                                @if ($subject->asset_name)
                                    <div class="text-xs text-slate-500 truncate max-w-[180px]">{{ $subject->asset_name }}</div>
                                @endif
                            @elseif (! empty($attributes['asset_no']))
                                <span class="font-mono font-semibold text-xs text-slate-900">{{ $attributes['asset_no'] }}</span>
                                @if (! empty($attributes['asset_name']))
                                    <div class="text-xs text-slate-500 truncate max-w-[180px]">{{ $attributes['asset_name'] }}</div>
                                @endif
                            @else
                                <span class="text-slate-400 text-xs font-sans">General system</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-xs font-sans text-slate-700">
                            @if (!empty($changes))
                                <div class="flex flex-wrap gap-1.5 items-center">
                                    @foreach ($changes as $change)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-slate-100 text-slate-800 border border-slate-200 shadow-2xs font-sans">
                                            <strong class="text-slate-700 font-semibold">{{ $change['label'] }}:</strong>
                                            @if (!empty($change['old']))
                                                <span class="text-slate-600">{{ $change['old'] }}</span>
                                                <span class="text-slate-400 font-medium px-0.5">to</span>
                                                <span class="font-semibold text-slate-900">{{ $change['new'] }}</span>
                                            @else
                                                <span class="font-semibold text-slate-900">{{ $change['new'] }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-400 text-xs font-sans">No attribute diff</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-sm text-slate-500">
                            No audit activities recorded in the system yet.
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if ($activities->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $activities->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Activity Filter Script -->
    <script>
        function filterActivities(query) {
            const q = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.activity-row');
            rows.forEach(function (row) {
                if (!q) {
                    row.style.display = '';
                    return;
                }
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        }
    </script>
@endsection
