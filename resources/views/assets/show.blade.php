@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-red-600 hover:underline">
            &larr; Back to Assets List
        </a>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-4 mb-6 pb-6 border-b border-neutral-200">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-3xl font-extrabold tracking-tight text-neutral-950">{{ $asset->asset_name }}</h2>
                @include('assets.partials.status-badge', ['status' => $asset->status])
            </div>
            <div class="flex items-center gap-2 mt-2">
                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-red-50 border border-red-200 text-base font-mono font-bold text-red-700">
                    {{ $asset->asset_no }}
                </span>
                @if ($asset->location || $asset->department)
                    <span class="text-sm font-semibold text-neutral-600">
                        &bull; {{ $asset->location ?: $asset->department }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('tags.print', ['ids' => [$asset->id]]) }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-base shadow-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print Tag</span>
            </a>

            <a href="{{ route('assets.move.create', $asset) }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border-2 border-neutral-950 bg-neutral-950 hover:bg-neutral-800 active:translate-y-[1px] text-white font-bold text-base transition">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Move Asset</span>
            </a>

            <a href="{{ route('assets.edit', $asset) }}"
               class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border-2 border-neutral-300 bg-white hover:bg-neutral-100 active:translate-y-[1px] text-neutral-900 font-bold text-base transition">
                <span>Edit Asset</span>
            </a>

            <form method="POST"
                  action="{{ route('assets.destroy', $asset) }}"
                  onsubmit="return confirm('Are you sure you want to delete asset {{ addslashes($asset->asset_no) }} ({{ addslashes($asset->asset_name) }})?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border-2 border-red-300 bg-white hover:bg-red-50 active:translate-y-[1px] text-red-700 font-bold text-base transition cursor-pointer">
                    <span>Delete</span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Asset Information -->
        <div class="lg:col-span-6 p-6 bg-white border-2 border-neutral-200 rounded-2xl shadow-xs">
            <h3 class="text-xl font-extrabold text-neutral-950 mb-4 pb-3 border-b border-neutral-200">Asset Information</h3>
            <dl class="divide-y divide-neutral-200 text-base">
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Asset No.</dt>
                    <dd class="col-span-2 font-mono font-bold text-neutral-950">{{ $asset->asset_no }}</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Asset Name</dt>
                    <dd class="col-span-2 font-bold text-neutral-950">{{ $asset->asset_name }}</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Model</dt>
                    <dd class="col-span-2 text-neutral-900">{{ $asset->model ?: '—' }}</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Serial No.</dt>
                    <dd class="col-span-2 font-mono text-neutral-900">{{ $asset->serial_no ?: '—' }}</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Date Acquired</dt>
                    <dd class="col-span-2 text-neutral-900">{{ $asset->date_acquired ? $asset->date_acquired->format('M d, Y') : '—' }}</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Department</dt>
                    <dd class="col-span-2 text-neutral-900">{{ $asset->department ?: '—' }}</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Location</dt>
                    <dd class="col-span-2 font-bold text-neutral-950">{{ $asset->location ?: '—' }}</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Status</dt>
                    <dd class="col-span-2">@include('assets.partials.status-badge', ['status' => $asset->status])</dd>
                </div>
                <div class="py-3.5 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-neutral-600">Notes</dt>
                    <dd class="col-span-2 text-neutral-800 whitespace-pre-line">{{ $asset->notes ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Property Tag Preview -->
        <div class="lg:col-span-6 p-6 bg-neutral-50 border-2 border-neutral-200 rounded-2xl shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-neutral-200">
                    <div>
                        <h3 class="text-xl font-extrabold text-neutral-950">Property Tag Preview</h3>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Configured size: {{ \App\Models\Setting::get('tag_width_cm', '10') }} cm &times; {{ \App\Models\Setting::get('tag_height_cm', '6') }} cm
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('tags.pdf', ['ids' => [$asset->id]]) }}"
                           class="px-3 py-1.5 rounded-lg border border-neutral-300 bg-white text-xs font-bold text-neutral-900 hover:bg-neutral-100 transition">
                            Download PDF
                        </a>
                        <a href="{{ route('tags.print', ['ids' => [$asset->id]]) }}"
                           class="px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white hover:bg-red-700 transition">
                            Open Print Sheet &rarr;
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto bg-clinical-grid p-5 rounded-xl border border-neutral-300 flex items-center justify-center">
                    <div class="shadow-md">
                        @include('tags.partials.tag-card', ['asset' => $asset, 'preview' => true])
                    </div>
                </div>
            </div>

            <p class="text-xs text-neutral-500 mt-4 text-center">
                Matches Bethel Baptist Hospital's paper tag format with embedded base64 logo and scannable QR code.
            </p>
        </div>
    </div>

    <!-- Transfer History -->
    <div class="p-6 bg-white border-2 border-neutral-200 rounded-2xl shadow-xs">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-neutral-200">
            <div>
                <h3 class="text-xl font-extrabold text-neutral-950">Location Transfer History</h3>
                <p class="text-xs text-neutral-500 mt-0.5">Complete audit log of where this asset has been stationed</p>
            </div>
            <a href="{{ route('assets.move.create', $asset) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 text-white font-bold text-sm hover:bg-red-700 transition">
                <span>Move Asset</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-neutral-950 text-white text-xs font-extrabold uppercase tracking-wider">
                        <th class="p-3.5">Date &amp; Time</th>
                        <th class="p-3.5">From Location</th>
                        <th class="p-3.5">To Location</th>
                        <th class="p-3.5">Moved By</th>
                        <th class="p-3.5">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 text-base">
                    @forelse ($asset->transfers as $transfer)
                        <tr class="hover:bg-neutral-50 transition">
                            <td class="p-3.5 whitespace-nowrap text-sm font-medium text-neutral-700 tabular-nums">
                                {{ $transfer->moved_at?->format('M d, Y h:i A') }}
                            </td>
                            <td class="p-3.5 text-neutral-600">{{ $transfer->from_location ?: 'Initial Registration' }}</td>
                            <td class="p-3.5 font-bold text-neutral-950">{{ $transfer->to_location }}</td>
                            <td class="p-3.5 font-semibold text-neutral-800">{{ $transfer->mover?->name ?? 'System' }}</td>
                            <td class="p-3.5 text-neutral-700">{{ $transfer->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-neutral-600">
                                No location transfers recorded for this asset yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
