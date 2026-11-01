@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-1.5 text-base font-bold text-blue-700 hover:underline mb-2">
                &larr; Back to Settings
            </a>
            <h2 class="text-3xl font-bold text-slate-900">Activity Log</h2>
            <p class="text-base text-slate-600 mt-1">
                Audit history of asset additions, edits, location transfers, Excel imports, and backups.
            </p>
        </div>
    </div>

    <div class="bg-white border-2 border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b-2 border-slate-200 text-sm font-bold uppercase tracking-wide text-slate-800">
                        <th class="p-4">Date &amp; Time</th>
                        <th class="p-4">User</th>
                        <th class="p-4">Activity Description</th>
                        <th class="p-4">Asset</th>
                        <th class="p-4">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-base">
                    @forelse ($activities as $activity)
                        @php
                            $subject = $activity->subject;
                            $attributes = $activity->properties['attributes'] ?? [];
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="p-4 whitespace-nowrap text-sm text-slate-700">
                                {{ $activity->created_at?->format('M d, Y h:i A') }}
                            </td>
                            <td class="p-4 font-semibold text-slate-900 whitespace-nowrap">
                                {{ $activity->causer?->name ?? 'System' }}
                            </td>
                            <td class="p-4 font-medium text-slate-900">
                                {{ $activity->description }}
                            </td>
                            <td class="p-4 font-mono text-sm">
                                @if ($subject && isset($subject->asset_no))
                                    @if (method_exists($subject, 'trashed') && ! $subject->trashed())
                                        <a href="{{ route('assets.show', $subject) }}" class="font-bold text-blue-700 hover:underline">
                                            {{ $subject->asset_no }}
                                        </a>
                                    @else
                                        <span class="font-bold text-slate-700">{{ $subject->asset_no }}</span>
                                    @endif
                                    <div class="font-sans text-xs text-slate-600">{{ $subject->asset_name ?? '' }}</div>
                                @elseif (! empty($attributes['asset_no']))
                                    <span class="font-bold text-slate-800">{{ $attributes['asset_no'] }}</span>
                                    <div class="font-sans text-xs text-slate-600">{{ $attributes['asset_name'] ?? '' }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-4 text-xs text-slate-600 font-mono">
                                @if (! empty($attributes))
                                    {{ collect($attributes)->only(['asset_no', 'asset_name', 'location', 'status'])->filter()->map(fn ($v, $k) => "$k: $v")->implode(' | ') }}
                                @elseif ($activity->properties && $activity->properties->count() > 0)
                                    {{ $activity->properties->map(fn ($v, $k) => is_scalar($v) ? "$k: $v" : '')->filter()->implode(' | ') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-600">
                                No activity recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activities->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
@endsection
