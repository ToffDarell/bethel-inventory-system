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

        <!-- Quick Filter / Search Bar (Client-Side) -->
        <x-card padding="p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="relative flex-1 min-w-[260px] max-w-md">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text"
                           id="activity-search"
                           placeholder="Filter by user, action, or asset number..."
                           oninput="filterActivities(this.value)"
                           class="w-full min-h-[44px] pl-10 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span>Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} of {{ $activities->total() }} audit events</span>
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
                        <th class="py-3 px-4">Audit Details</th>
                    </tr>
                </x-slot:header>

                @forelse ($activities as $activity)
                    @php
                        $subject = $activity->subject;
                        $attributes = $activity->properties['attributes'] ?? [];
                        $isAsset = $subject && isset($subject->asset_no);
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
                                <span class="text-sm font-semibold text-slate-900">
                                    {{ $activity->causer?->name ?? 'System' }}
                                </span>
                            </div>
                        </td>

                        <td class="py-3 px-4 text-sm font-medium text-slate-900">
                            {{ $activity->description }}
                        </td>

                        <td class="py-3 px-4">
                            @if ($isAsset)
                                @if (method_exists($subject, 'trashed') && ! $subject->trashed())
                                    <a href="{{ route('assets.show', $subject) }}" class="inline-flex items-center gap-1 font-mono font-semibold text-xs text-[#0f5d8c] hover:underline">
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

                        <td class="py-3 px-4 text-xs font-mono text-slate-600">
                            @if (! empty($attributes))
                                <div class="space-y-0.5">
                                    @foreach (collect($attributes)->only(['asset_no', 'asset_name', 'location', 'department', 'status'])->filter() as $k => $v)
                                        <span class="inline-block bg-slate-100 text-slate-800 px-1.5 py-0.5 rounded border border-slate-200 text-[11px] mr-1 mb-1">
                                            <strong>{{ $k }}:</strong> {{ $v }}
                                        </span>
                                    @endforeach
                                </div>
                            @elseif ($activity->properties && $activity->properties->count() > 0)
                                <div class="space-y-0.5">
                                    @foreach ($activity->properties as $k => $v)
                                        @if (is_scalar($v))
                                            <span class="inline-block bg-slate-100 text-slate-800 px-1.5 py-0.5 rounded border border-slate-200 text-[11px] mr-1 mb-1">
                                                <strong>{{ $k }}:</strong> {{ $v }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-400 text-xs">None</span>
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
