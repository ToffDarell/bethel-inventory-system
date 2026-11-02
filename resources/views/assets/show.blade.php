@extends('layouts.app')

@section('content')
    <!-- Asset Detail Header -->
    <div class="mb-6">
        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 hover:underline mb-2 transition-colors">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>Back to Assets</span>
        </a>

        <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">{{ $asset->asset_name }}</h1>
                    <x-badge :status="$asset->status" />
                </div>

                <div class="flex flex-wrap items-center gap-2.5 mt-2 text-sm">
                    <span class="font-mono text-xs font-medium text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded">
                        {{ $asset->asset_no }}
                    </span>
                    @if ($asset->location || $asset->department)
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-slate-600">
                            {{ $asset->location ?: 'No room assigned' }}
                            @if ($asset->department)
                                <span class="text-slate-400">({{ $asset->department }})</span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>

            <!-- Header Actions: 1 Primary (Print Tag), Secondary Outlines (Move, Edit, Delete) -->
            <div class="flex flex-wrap items-center gap-2.5">
                <x-button variant="primary" :href="route('tags.print', ['ids' => [$asset->id]])">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print Tag</span>
                </x-button>

                <x-button variant="secondary" :href="route('assets.move.create', $asset)">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Move Asset</span>
                </x-button>

                <x-button variant="secondary" :href="route('assets.edit', $asset)">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Asset</span>
                </x-button>

                <form method="POST"
                      action="{{ route('assets.destroy', $asset) }}"
                      onsubmit="return confirm('Are you sure you want to delete asset {{ addslashes($asset->asset_no) }} ({{ addslashes($asset->asset_name) }})?');">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger-outline" type="submit">
                        <svg class="w-4 h-4 text-[#b91c1c]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Delete</span>
                    </x-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Two-Column Layout: Left = Asset Details, Right = Property Tag Preview & Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <!-- Left Column: Asset Information -->
        <div class="lg:col-span-6">
            <x-card title="Asset Information" subtitle="Registered identification, location, and condition details">
                <dl class="divide-y divide-slate-100 text-[15px]">
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Asset No.</dt>
                        <dd class="col-span-2 font-mono font-medium text-slate-900">{{ $asset->asset_no }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Asset Name</dt>
                        <dd class="col-span-2 font-medium text-slate-900">{{ $asset->asset_name }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Model / Brand</dt>
                        <dd class="col-span-2 text-slate-900">{{ $asset->model ?: '—' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Serial No.</dt>
                        <dd class="col-span-2 font-mono text-slate-800">{{ $asset->serial_no ?: '—' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Date Acquired</dt>
                        <dd class="col-span-2 text-slate-900 tabular-nums">{{ $asset->date_acquired ? $asset->date_acquired->format('M d, Y') : '—' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Department</dt>
                        <dd class="col-span-2 text-slate-900">{{ $asset->department ?: '—' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Location</dt>
                        <dd class="col-span-2 font-medium text-slate-900">{{ $asset->location ?: '—' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 items-center">
                        <dt class="text-slate-500 font-medium">Status</dt>
                        <dd class="col-span-2">
                            <x-badge :status="$asset->status" />
                        </dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-slate-500 font-medium">Notes</dt>
                        <dd class="col-span-2 text-slate-700 whitespace-pre-line">{{ $asset->notes ?: '—' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <!-- Right Column: Property Tag Preview & Print Actions -->
        <div class="lg:col-span-6">
            <x-card>
                <x-slot:header>
                    <div class="flex flex-wrap items-center justify-between gap-3 w-full">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Property Tag Preview</h3>
                            <p class="text-xs text-slate-500 mt-0.5 tabular-nums">
                                Configured dimension: {{ \App\Models\Setting::get('tag_width_cm', '10') }} cm &times; {{ \App\Models\Setting::get('tag_height_cm', '6') }} cm
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('tags.pdf', ['ids' => [$asset->id]]) }}"
                               class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Download PDF</span>
                            </a>
                            <a href="{{ route('tags.print', ['ids' => [$asset->id]]) }}"
                               class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-800 transition-colors">
                                <span>Open Print Sheet &rarr;</span>
                            </a>
                        </div>
                    </div>
                </x-slot:header>

                <!-- Tactile Physical Tag Stage (The Unslop Moment) -->
                <div class="overflow-x-auto bg-slate-100/80 p-6 rounded-lg border border-slate-200 flex items-center justify-center">
                    <div class="bg-white shadow-sm">
                        @include('tags.partials.tag-card', ['asset' => $asset, 'preview' => true])
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-4 text-center">
                    Matches Bethel Baptist Hospital's physical property tag layout with embedded base64 logo and scannable QR code
                </p>
            </x-card>
        </div>
    </div>

    <!-- Location Transfer History -->
    <x-card padding="p-0">
        <x-slot:header>
            <div class="flex flex-wrap items-center justify-between gap-3 w-full">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Location Transfer History</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Chronological audit trail of room and department assignments</p>
                </div>
                <x-button variant="secondary" :href="route('assets.move.create', $asset)">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Move Asset</span>
                </x-button>
            </div>
        </x-slot:header>

        @if ($asset->transfers->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-[15px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th class="py-3.5 px-4">Date &amp; Time</th>
                            <th class="py-3.5 px-4">From Location</th>
                            <th class="py-3.5 px-4">To Location</th>
                            <th class="py-3.5 px-4">Moved By</th>
                            <th class="py-3.5 px-4">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 [&>tr:hover]:bg-slate-50/90 transition-colors">
                        @foreach ($asset->transfers as $transfer)
                            <tr>
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs text-slate-600 tabular-nums">
                                    {{ $transfer->moved_at?->format('M d, Y h:i A') }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ $transfer->from_location ?: 'Initial registration' }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-900">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#b91c1c]"></span>
                                        <span>{{ $transfer->to_location }}</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    {{ $transfer->mover?->name ?? 'System' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ $transfer->notes ?: '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6">
                <x-empty-state
                    title="No location transfers recorded yet"
                    description="When this equipment is moved to another ward, station, or room, each transfer will be logged here"
                >
                    <x-slot:action>
                        <x-button variant="secondary" :href="route('assets.move.create', $asset)">
                            Record First Transfer
                        </x-button>
                    </x-slot:action>
                </x-empty-state>
            </div>
        @endif
    </x-card>
@endsection
