@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-3xl font-bold text-slate-900">{{ $asset->asset_name }}</h2>
                @include('assets.partials.status-badge', ['status' => $asset->status])
            </div>
            <p class="text-lg font-mono font-bold text-blue-700 mt-1">{{ $asset->asset_no }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('tags.print', ['ids' => [$asset->id]]) }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-base shadow-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print Tag</span>
            </a>

            <a href="{{ route('assets.move.create', $asset) }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-lg border-2 border-blue-700 bg-white hover:bg-blue-50 text-blue-700 font-bold text-base transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Move Asset</span>
            </a>

            <a href="{{ route('assets.edit', $asset) }}"
               class="inline-flex items-center gap-2 px-4 py-3 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-base transition">
                <span>Edit Asset</span>
            </a>

            <form method="POST"
                  action="{{ route('assets.destroy', $asset) }}"
                  onsubmit="return confirm('Are you sure you want to delete asset {{ addslashes($asset->asset_no) }} ({{ addslashes($asset->asset_name) }})?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-3 rounded-lg border-2 border-red-300 bg-white hover:bg-red-50 text-red-700 font-bold text-base transition cursor-pointer">
                    <span>Delete</span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Asset Information -->
        <div class="lg:col-span-7 p-6 bg-white border-2 border-slate-200 rounded-xl">
            <h3 class="text-xl font-bold text-slate-900 mb-4 pb-3 border-b border-slate-200">Asset Information</h3>
            <dl class="divide-y divide-slate-200 text-base">
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Asset No.</dt>
                    <dd class="col-span-2 font-mono font-bold text-slate-900">{{ $asset->asset_no }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Asset Name</dt>
                    <dd class="col-span-2 font-semibold text-slate-900">{{ $asset->asset_name }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Model</dt>
                    <dd class="col-span-2 text-slate-900">{{ $asset->model ?: '—' }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Serial No.</dt>
                    <dd class="col-span-2 font-mono text-slate-900">{{ $asset->serial_no ?: '—' }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Date Acquired</dt>
                    <dd class="col-span-2 text-slate-900">{{ $asset->date_acquired ? $asset->date_acquired->format('M d, Y') : '—' }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Department</dt>
                    <dd class="col-span-2 text-slate-900">{{ $asset->department ?: '—' }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Location</dt>
                    <dd class="col-span-2 font-semibold text-slate-900">{{ $asset->location ?: '—' }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Status</dt>
                    <dd class="col-span-2">@include('assets.partials.status-badge', ['status' => $asset->status])</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="font-bold text-slate-600">Notes</dt>
                    <dd class="col-span-2 text-slate-800 whitespace-pre-line">{{ $asset->notes ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Property Tag Preview -->
        <div class="lg:col-span-5 p-6 bg-slate-50 border-2 border-slate-200 rounded-xl">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
                <h3 class="text-xl font-bold text-slate-900">Property Tag Preview</h3>
                <div class="flex items-center gap-3">
                    <a href="{{ route('tags.pdf', ['ids' => [$asset->id]]) }}"
                       class="text-sm font-bold text-slate-700 hover:text-blue-700 hover:underline">
                        Download PDF
                    </a>
                    <a href="{{ route('tags.print', ['ids' => [$asset->id]]) }}"
                       class="text-sm font-bold text-blue-700 hover:underline">
                        Open Print Sheet &rarr;
                    </a>
                </div>
            </div>

            @if (view()->exists('tags.partials.tag-card'))
                <div class="overflow-x-auto bg-white p-3 rounded-lg border border-slate-300">
                    @include('tags.partials.tag-card', ['asset' => $asset, 'preview' => true])
                </div>
            @else
                <div class="p-4 bg-white border-2 border-slate-800 text-sm">
                    <p class="font-bold text-center">{{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</p>
                    <p class="text-xs text-center text-slate-600 mb-3">{{ \App\Models\Setting::get('hospital_address') }}</p>
                    <p><strong>Date Acquired:</strong> {{ $asset->date_acquired?->format('Y-m-d') ?: '—' }}</p>
                    <p><strong>Asset Name:</strong> {{ $asset->asset_name }}</p>
                    <p><strong>Model:</strong> {{ $asset->model ?: '—' }}</p>
                    <p><strong>Serial No.:</strong> {{ $asset->serial_no ?: '—' }}</p>
                    <p><strong>Location:</strong> {{ $asset->location ?: '—' }}</p>
                    <p class="font-mono font-bold mt-1"><strong>Asset No.:</strong> {{ $asset->asset_no }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Transfer History -->
    <div class="p-6 bg-white border-2 border-slate-200 rounded-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900">Location Transfer History</h3>
            <a href="{{ route('assets.move.create', $asset) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-700 text-white font-bold text-sm hover:bg-blue-800">
                <span>Move Asset</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b-2 border-slate-200 text-sm font-bold uppercase text-slate-800">
                        <th class="p-3">Date &amp; Time</th>
                        <th class="p-3">From Location</th>
                        <th class="p-3">To Location</th>
                        <th class="p-3">Moved By</th>
                        <th class="p-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-base">
                    @forelse ($asset->transfers as $transfer)
                        <tr>
                            <td class="p-3 whitespace-nowrap">{{ $transfer->moved_at?->format('M d, Y h:i A') }}</td>
                            <td class="p-3 text-slate-700">{{ $transfer->from_location ?: '—' }}</td>
                            <td class="p-3 font-bold text-slate-900">{{ $transfer->to_location }}</td>
                            <td class="p-3 text-slate-700">{{ $transfer->mover?->name ?? 'System' }}</td>
                            <td class="p-3 text-slate-700">{{ $transfer->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-600">
                                No location transfers recorded for this asset yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
