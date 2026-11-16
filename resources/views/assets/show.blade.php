@extends('layouts.app')

@section('content')
    <!-- Asset Detail Header -->
    <div class="mb-6">
        <a href="{{ $asset->trashed() ? route('assets.index', ['tab' => 'archived']) : route('assets.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-2xs hover:border-slate-400 hover:text-slate-900 transition-all duration-150 mb-4">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>{{ $asset->trashed() ? 'Back to Archive' : 'Back to Assets' }}</span>
        </a>

        @if ($asset->trashed())
            <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-950 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-800 border border-amber-200 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-amber-900">Archived Equipment Record</h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-200/80 text-amber-900 border border-amber-300">
                                In Archive
                            </span>
                        </div>
                        <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                            Archived on {{ $asset->deleted_at->format('F j, Y \a\t g:i A') }} ({{ $asset->deleted_at->diffForHumans() }}).
                            This equipment is currently excluded from active inventory lists, property audits, and active depreciation calculations.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if (auth()->user()?->isAdmin())
                        <form method="POST" action="{{ route('assets.restore', $asset) }}">
                            @csrf
                            <input type="hidden" name="redirect_to" value="show">
                            <button type="submit"
                                    class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Restore to Active</span>
                            </button>
                        </form>
                        <button type="button"
                                onclick="openForceDeleteModal()"
                                class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-red-300 bg-white text-brand-red font-semibold text-xs hover:bg-red-50 hover:border-red-400 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Permanently Delete</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif

        @if (session('reassigned_new_no') || $asset->needs_tag_reprint)
            @php
                $latestHistory = $asset->numberHistories?->first();
                $oldNo = session('reassigned_old_no') ?? $latestHistory?->old_no;
            @endphp
            <div class="mb-4 p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 flex flex-wrap items-center justify-between gap-4 shadow-2xs">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-800 border border-amber-300 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-amber-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-amber-950">Property Tag Reprint Needed</h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-200 text-amber-950 border border-amber-300">
                                Reprint Flagged
                            </span>
                        </div>
                        <p class="text-xs text-amber-900 mt-1 leading-relaxed">
                            @if ($oldNo)
                                This asset's number changed from <strong class="font-mono font-bold">{{ $oldNo }}</strong> to <strong class="font-mono font-bold">{{ $asset->asset_no }}</strong> because its department changed. Print the new tag so the equipment matches the register.
                            @else
                                Print the new tag so the equipment matches the register.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('tags.print', ['id' => $asset->id]) }}"
                       target="_blank"
                       class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary hover:bg-slate-800 text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Print Tag</span>
                    </a>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">{{ $asset->asset_name }}</h1>
                    <x-badge :status="$asset->status" />
                    <x-condition-badge :condition="$asset->asset_condition" />
                    @if ($asset->category)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                            {{ $asset->category->name }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2.5 mt-2 text-sm">
                    <span class="font-mono text-xs font-medium text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded">
                        {{ $asset->asset_no }}
                    </span>
                    @if ($asset->departmentRelation && ! empty($asset->departmentRelation->code) && ! str_starts_with($asset->asset_no, \App\Models\Setting::get('asset_no_prefix', 'BBH') . $asset->departmentRelation->code))
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded" title="Reverted number does not match current department code ({{ $asset->departmentRelation->code }})">
                            <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>Note: Number does not match department code ({{ $asset->departmentRelation->code }})</span>
                        </span>
                    @endif
                    @if ($asset->location || $asset->department || $asset->section)
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-slate-600">
                            {{ $asset->location ?: 'No room assigned' }}
                            @if ($asset->department || $asset->section)
                                <span class="text-slate-400">
                                    ({{ implode(' / ', array_filter([$asset->department, $asset->section])) }})
                                </span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <x-button variant="primary" :href="route('tags.print', ['ids' => [$asset->id]])">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print Tag</span>
                </x-button>

                @if ($asset->trashed())
                    @if (auth()->user()?->isAdmin())
                        <form method="POST" action="{{ route('assets.restore', $asset) }}" class="inline">
                            @csrf
                            <input type="hidden" name="redirect_to" value="show">
                            <x-button variant="secondary" type="submit">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Restore Asset</span>
                            </x-button>
                        </form>

                        <button type="button"
                                onclick="openForceDeleteModal()"
                                class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-red-300 bg-white text-brand-red font-semibold text-xs hover:bg-red-50 hover:border-red-400 transition-colors cursor-pointer">
                            <svg class="w-4 h-4 text-brand-red" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Permanently Delete</span>
                        </button>
                    @endif
                @else
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

                    @if (auth()->user()?->isAdmin())
                        <button type="button"
                                onclick="openArchiveModal()"
                                class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 hover:text-amber-800 hover:border-amber-300 transition-colors cursor-pointer">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            <span>Archive</span>
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Two-Column Layout: Left = Asset Details, Right = Property Tag Preview & Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <!-- Left Column: Asset Information & Value (Financials) -->
        <div class="lg:col-span-6 space-y-6">
            <x-card title="Asset Information" subtitle="Registered identification, location, and condition details">
                <dl class="divide-y divide-slate-100 text-[15px]">
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Asset No.</dt>
                        <dd class="col-span-2 font-mono font-medium text-slate-900">{{ $asset->asset_no }}</dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Asset Description</dt>
                        <dd class="col-span-2 font-medium text-slate-900">{{ $asset->asset_name }}</dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Category</dt>
                        <dd class="col-span-2 text-slate-900 font-medium">
                            {{ $asset->category ? $asset->category->name : 'Uncategorized' }}
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Brand</dt>
                        <dd class="col-span-2 text-slate-900">
                            {{ $asset->brand ?: 'Not specified' }}
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Model</dt>
                        <dd class="col-span-2 text-slate-900">
                            {{ $asset->model ?: 'None' }}
                        </dd>
                    </div>
                    @if ($asset->vendor)
                        <div class="py-3.5 grid grid-cols-3 gap-4">
                            <dt class="text-slate-600 font-medium">Vendor</dt>
                            <dd class="col-span-2 text-slate-900 flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $asset->vendor->name }}</span>
                                @if ($asset->vendor->is_donation)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Donation
                                    </span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Serial Number</dt>
                        <dd class="col-span-2 text-slate-900">
                            @if ($asset->serial_no)
                                <span class="font-mono text-slate-800">{{ $asset->serial_no }}</span>
                            @else
                                <span class="text-slate-400">None</span>
                            @endif
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Year Bought</dt>
                        <dd class="col-span-2 text-slate-900 tabular-nums font-mono">
                            {{ $asset->date_acquired ? $asset->date_acquired->format('Y') : 'Not recorded' }}
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Department</dt>
                        <dd class="col-span-2 text-slate-900">
                            {{ $asset->department ?: 'Unassigned' }}
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Section</dt>
                        <dd class="col-span-2 text-slate-900">
                            {{ $asset->section ?: 'None' }}
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Location</dt>
                        <dd class="col-span-2 font-medium text-slate-900">
                            {{ $asset->location ?: 'Unassigned' }}
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Assigned Personnel</dt>
                        <dd class="col-span-2 text-slate-900">
                            {{ $asset->assigned_personnel ?: 'Unassigned' }}
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4 items-center">
                        <dt class="text-slate-600 font-medium">Status</dt>
                        <dd class="col-span-2">
                            <x-badge :status="$asset->status" />
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4 items-center">
                        <dt class="text-slate-600 font-medium">Asset Condition</dt>
                        <dd class="col-span-2">
                            <x-condition-badge :condition="$asset->asset_condition" />
                        </dd>
                    </div>
                    <div class="py-3.5 grid grid-cols-3 gap-4">
                        <dt class="text-slate-600 font-medium">Remarks</dt>
                        <dd class="col-span-2 text-slate-700 whitespace-pre-line">
                            {{ $asset->notes ?: 'None' }}
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <!-- Right Column: Property Tag Preview & Location Transfer History -->
        <div class="lg:col-span-6 space-y-6">
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

                <div class="overflow-x-auto bg-slate-100/80 p-3.5 sm:p-4 rounded-lg border border-slate-200 flex items-center justify-center">
                    <div class="bg-white shadow-sm">
                        @include('tags.partials.tag-card', ['asset' => $asset, 'preview' => true])
                    </div>
                </div>
            </x-card>

            <!-- Location Transfer History as a Timeline -->
            <x-card>
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
                    <div class="relative pl-6 sm:pl-8 before:absolute before:left-[11px] sm:before:left-[15px] before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200 space-y-4 pt-2 max-h-[420px] overflow-y-auto pr-1">
                        @foreach ($asset->transfers as $index => $transfer)
                            <div class="relative flex items-start gap-3">
                                <!-- Node indicator: Primary for current location, muted border for past -->
                                <div class="absolute -left-6 sm:-left-8 top-1 w-6 h-6 rounded-full flex items-center justify-center {{ $index === 0 ? 'bg-primary text-white ring-4 ring-primary-soft' : 'bg-white border-2 border-slate-300 text-slate-500' }}">
                                    @if ($index === 0)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    @endif
                                </div>

                                <!-- Transfer Content Card -->
                                <div class="flex-1 bg-slate-50/70 border border-slate-200/80 rounded-xl p-3.5 hover:border-slate-300 transition-colors">
                                    <div class="flex flex-wrap items-center justify-between gap-1.5 pb-2 border-b border-slate-200/60 text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-slate-900 tabular-nums">
                                                {{ $transfer->moved_at?->format('M d, Y') }}
                                            </span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span class="text-slate-500 tabular-nums">
                                                {{ $transfer->moved_at?->format('h:i A') }}
                                            </span>
                                            @if ($index === 0)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium bg-primary-soft text-primary border border-primary/20">
                                                    Current
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-slate-500">
                                            Moved by <strong class="font-medium text-slate-700">{{ $transfer->mover?->name ?? 'System' }}</strong>
                                        </span>
                                    </div>

                                    <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[11px] text-slate-500 font-medium">From</span>
                                            <span class="font-medium text-slate-700 bg-white border border-slate-200 px-2 py-0.5 rounded text-xs">
                                                {{ $transfer->from_location ?: 'Initial registration' }}
                                            </span>
                                        </div>

                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>

                                        <div class="flex items-center gap-1">
                                            <span class="text-[11px] text-primary font-semibold">To</span>
                                            <span class="font-semibold text-slate-900 bg-white border border-primary/30 shadow-2xs px-2 py-0.5 rounded text-xs">
                                                {{ $transfer->to_location }}
                                            </span>
                                        </div>
                                    </div>

                                    @if ($transfer->notes)
                                        <div class="mt-2 pt-2 border-t border-slate-200/60 text-xs text-slate-600 flex items-start gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                            </svg>
                                            <span class="text-[11px] leading-relaxed">{{ $transfer->notes }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6">
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

            @if ($asset->numberHistories && $asset->numberHistories->isNotEmpty())
                <div class="mt-6">
                    <x-card title="Asset Number History" subtitle="Track former property tag numbers reassigned during department transfers">
                        @if (auth()->user()?->isAdmin())
                            <x-slot:actions>
                                <form method="POST" action="{{ route('assets.revert-number', $asset) }}" onsubmit="return confirm('Are you sure you want to revert this asset\'s number from {{ $asset->asset_no }} back to {{ $asset->numberHistories->first()->old_no }}? The reprint flag will be cleared.');">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5" title="Restore previous asset number">
                                        <svg class="w-3.5 h-3.5 text-amber-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                                        </svg>
                                        <span>Revert to previous number</span>
                                    </button>
                                </form>
                            </x-slot:actions>
                        @endif
                        <div class="space-y-3">
                            @foreach ($asset->numberHistories as $history)
                                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-semibold text-slate-500 line-through">{{ $history->old_no }}</span>
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                            <span class="font-mono font-bold text-slate-900 bg-white border border-slate-300 px-2 py-0.5 rounded shadow-2xs">{{ $history->new_no }}</span>
                                        </div>
                                        @if ($history->reason)
                                            <span class="text-slate-600">&bull; {{ $history->reason }}</span>
                                        @endif
                                    </div>
                                    <div class="text-slate-500 text-[11px] tabular-nums">
                                        Changed by <strong class="text-slate-700">{{ $history->user?->name ?? 'System' }}</strong> &bull; {{ ($history->changed_at ?? $history->created_at)->format('M j, Y g:i A') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                </div>
            @endif
        </div>
    </div>

    @if (auth()->user()?->isAdmin() && isset($depreciation))
        <div class="mb-6">
            <x-card title="Depreciation & Valuation" subtitle="Estimated value and straight-line depreciation based on category rules">
                @if ($depreciation->isCalculable())
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-slate-500 font-medium text-xs block">Year Bought</span>
                            <strong class="text-slate-900 font-semibold text-sm mt-0.5 block tabular-nums">
                                {{ $asset->date_acquired ? $asset->date_acquired->format('Y') : 'Not set' }}
                            </strong>
                        </div>
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-slate-500 font-medium text-xs block">Cost</span>
                            <strong class="text-slate-900 font-semibold text-sm mt-0.5 block tabular-nums">
                                ₱{{ number_format($depreciation->getCost(), 2) }}
                            </strong>
                        </div>
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-slate-500 font-medium text-xs block">Accumulated</span>
                            <strong class="text-slate-900 font-semibold text-sm mt-0.5 block tabular-nums text-slate-700">
                                ₱{{ number_format($depreciation->getAccumulatedDepreciation(), 2) }}
                            </strong>
                        </div>
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="text-slate-500 font-medium text-xs block">Current value</span>
                            <strong class="text-slate-900 font-bold text-base mt-0.5 block tabular-nums text-primary">
                                ₱{{ number_format($depreciation->getCurrentValue(), 2) }}
                            </strong>
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-600 mb-1.5">
                                <span class="font-medium">Loss of value over time</span>
                                <span class="tabular-nums font-semibold text-slate-900">{{ $depreciation->getMonthsElapsed() }} of {{ $depreciation->getTotalMonths() }} months elapsed ({{ round($depreciation->getUsagePercentage()) }}% of depreciable life elapsed)</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-primary h-2.5 rounded-full transition-all duration-300" style="width: {{ $depreciation->getUsagePercentage() }}%"></div>
                            </div>
                        </div>

                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-2">
                            <div class="p-2.5 rounded bg-slate-50 border border-slate-100">
                                <dt class="text-slate-500">Value lost per year</dt>
                                <dd class="tabular-nums font-semibold text-slate-800 mt-0.5">₱{{ number_format($depreciation->getYearlyDepreciation(), 2) }}</dd>
                            </div>
                            <div class="p-2.5 rounded bg-slate-50 border border-slate-100">
                                <dt class="text-slate-500">Expected end date:</dt>
                                <dd class="tabular-nums font-semibold text-slate-800 mt-0.5">{{ $depreciation->getEndDate() ? $depreciation->getEndDate()->format('M Y') : 'N/A' }}</dd>
                            </div>
                            <div class="p-2.5 rounded bg-slate-50 border border-slate-100">
                                <dt class="text-slate-500">Disposal value floor:</dt>
                                <dd class="tabular-nums font-semibold text-slate-800 mt-0.5">₱{{ number_format($depreciation->getDisposalValue(), 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-200">
                        <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-900">
                            @if ($depreciation->getMissingReason() === 'Useful life not set')
                                Useful life not set
                            @elseif ($depreciation->getMissingReason() === 'Select a category to calculate')
                                Select a category to calculate
                            @else
                                {{ $depreciation->getMissingReason() ?? 'Select a category and enter a cost to calculate' }}
                            @endif
                        </p>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                            Depreciation estimates require both a valid category (with useful life and disposal percent) and an acquisition cost.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('assets.edit', $asset) }}" class="min-h-[38px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg shadow-2xs hover:bg-slate-50 hover:border-slate-400 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1 transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                                <span>Edit Asset Details</span>
                            </a>
                        </div>
                    </div>
                @endif
            </x-card>
        </div>
    @endif
    </div>

    @if (! $asset->trashed() && auth()->user()?->isAdmin())
        <!-- Archive Confirmation Modal -->
        <div id="archive-modal"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden transition-opacity"
             role="dialog"
             aria-modal="true"
             aria-labelledby="archive-modal-title"
             onclick="closeArchiveModal()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150"
                 onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-full bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="archive-modal-title" class="text-base font-bold text-slate-900 leading-tight">
                                Archive Asset
                            </h3>
                            <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                                Move <strong class="font-semibold text-slate-900">{{ $asset->asset_name }}</strong> (<span class="font-mono text-slate-700">{{ $asset->asset_no }}</span>) to the archive? It will be removed from active inventory and audits, but all historical records and logs are preserved.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button"
                                id="archive-cancel-btn"
                                onclick="closeArchiveModal()"
                                class="min-h-[38px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg shadow-2xs hover:bg-slate-50 hover:border-slate-400 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="min-h-[38px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-2xs focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-1 transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                <span>Move to Archive</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($asset->trashed() && auth()->user()?->isAdmin())
        <!-- Permanent Delete Confirmation Modal -->
        <div id="force-delete-modal"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden transition-opacity"
             role="dialog"
             aria-modal="true"
             aria-labelledby="force-delete-modal-title"
             onclick="closeForceDeleteModal()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150"
                 onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-full bg-red-50 text-brand-red border border-red-200 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-brand-red" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="force-delete-modal-title" class="text-base font-bold text-slate-900 leading-tight">
                                Permanently Delete Asset
                            </h3>
                            <p class="text-xs text-red-600 font-semibold mt-1">
                                Caution: This action is permanent and cannot be undone!
                            </p>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Are you sure you want to completely erase <strong class="font-semibold text-slate-900">{{ $asset->asset_name }}</strong> (<span class="font-mono text-slate-700">{{ $asset->asset_no }}</span>)? All records, movements, and logs will be permanently deleted from the database.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button"
                                id="force-delete-cancel-btn"
                                onclick="closeForceDeleteModal()"
                                class="min-h-[38px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg shadow-2xs hover:bg-slate-50 hover:border-slate-400 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('assets.force-delete', $asset) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="min-h-[38px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-brand-red hover:bg-red-700 rounded-lg shadow-2xs focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-red focus-visible:ring-offset-1 transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Permanently Delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (auth()->user()?->isAdmin())
    <script>
        function openArchiveModal() {
            const modal = document.getElementById('archive-modal');
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => document.getElementById('archive-cancel-btn')?.focus(), 50);
            }
        }

        function closeArchiveModal() {
            const modal = document.getElementById('archive-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openForceDeleteModal() {
            const modal = document.getElementById('force-delete-modal');
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => document.getElementById('force-delete-cancel-btn')?.focus(), 50);
            }
        }

        function closeForceDeleteModal() {
            const modal = document.getElementById('force-delete-modal');
            if (modal) modal.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeArchiveModal();
                closeForceDeleteModal();
            }
        });
    </script>
    @endif
@endsection
