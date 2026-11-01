@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 hover:underline mb-1.5">
                &larr; Back to Settings
            </a>
            <h2 class="text-2xl font-extrabold text-red-600 tracking-tight">Activity Log</h2>
            <p class="text-sm text-neutral-600 mt-0.5">
                Audit history of asset additions, edits, location transfers, Excel imports, and backups.
            </p>
        </div>
    </div>

    <div class="bg-white border-2 border-red-100 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-red-600 text-[11px] font-bold uppercase tracking-wider text-white">
                        <th class="py-3 px-4">Date &amp; Time</th>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Activity Description</th>
                        <th class="py-3 px-4">Asset</th>
                        <th class="py-3 px-4">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-100 text-sm">
                    @forelse ($activities as $activity)
                        @php
                            $subject = $activity->subject;
                            $attributes = $activity->properties['attributes'] ?? [];
                        @endphp
                        <tr class="hover:bg-red-50 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap text-xs text-neutral-700">
                                {{ $activity->created_at?->format('M d, Y h:i A') }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-neutral-900 whitespace-nowrap">
                                {{ $activity->causer?->name ?? 'System' }}
                            </td>
                            <td class="py-3 px-4 font-medium text-neutral-900">
                                {{ $activity->description }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs">
                                @if ($subject && isset($subject->asset_no))
                                    @if (method_exists($subject, 'trashed') && ! $subject->trashed())
                                        <a href="{{ route('assets.show', $subject) }}" class="font-bold text-red-600 hover:underline">
                                            {{ $subject->asset_no }}
                                        </a>
                                    @else
                                        <span class="font-bold text-neutral-700">{{ $subject->asset_no }}</span>
                                    @endif
                                    <div class="font-sans text-[11px] text-neutral-500">{{ $subject->asset_name ?? '' }}</div>
                                @elseif (! empty($attributes['asset_no']))
                                    <span class="font-bold text-red-600">{{ $attributes['asset_no'] }}</span>
                                    <div class="font-sans text-[11px] text-neutral-500">{{ $attributes['asset_name'] ?? '' }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-neutral-600 font-mono">
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
                            <td colspan="5" class="p-8 text-center text-sm text-neutral-600">
                                No activity recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activities->hasPages())
            <div class="p-4 border-t border-red-100 bg-red-50">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
@endsection
