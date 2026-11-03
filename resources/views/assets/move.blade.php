@extends('layouts.app')

@section('content')
    <div class="max-w-2xl">
        <x-page-header
            title="Move Asset"
            :subtitle="'Update the room or department of ' . $asset->asset_name . ' (' . $asset->asset_no . ') and log the transfer'"
            :backUrl="route('assets.show', $asset)"
            :backLabel="'Back to ' . $asset->asset_no"
        />

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-950" role="alert" tabindex="-1">
                <p class="text-sm font-semibold text-red-900 mb-1">Please check the form below</p>
                <ul class="list-disc list-inside text-xs text-red-800 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('assets.move.store', $asset) }}" class="space-y-6">
            @csrf

            <x-card title="Transfer Details" subtitle="Record where this equipment is being stationed">
                <div class="space-y-5">
                    <!-- Current Assignment Summary -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between gap-3 mb-2.5">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipment to Transfer</span>
                            <span class="font-mono text-xs font-medium text-slate-800 bg-white border border-slate-200 px-2.5 py-1 rounded">
                                {{ $asset->asset_no }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0 text-[#0f5d8c]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $asset->asset_name }}</h3>
                                <p class="text-xs text-slate-600 mt-0.5">
                                    Current Location: <strong class="text-slate-800 font-medium">{{ $asset->location ?: 'Unassigned' }}</strong>
                                    @if ($asset->department)
                                        <span class="text-slate-300">&bull;</span> <span class="text-slate-500">{{ $asset->department }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <x-form-field
                        label="New Location"
                        name="to_location"
                        :required="true"
                        :autofocus="true"
                        placeholder="ND-Station 3 Room 39"
                        help="Enter the ward, station, or room where the asset is now located"
                    />

                    <div>
                        <x-form-field
                            label="Department"
                            name="department"
                            list="move-department-options"
                            :value="$asset->department"
                            placeholder="Nursing Station 3, ER, ICU, Pharmacy"
                            help="Update the responsible hospital unit if the asset changed departments"
                        />
                        <datalist id="move-department-options">
                            @foreach ($departments ?? [] as $dept)
                                <option value="{{ $dept }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <x-form-field
                        label="Reason / Notes"
                        name="notes"
                        type="textarea"
                        placeholder="Transferred for patient admission in Room 39"
                        help="Optional reason or handover note recorded in the transfer audit log"
                    />
                </div>
            </x-card>

            <!-- Sticky Save & Cancel Bar -->
            <div class="sticky bottom-0 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-xl p-4 shadow-2xs flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    Transfer timestamp and your user account will be logged automatically
                </p>
                <div class="flex items-center gap-3">
                    <x-button variant="secondary" :href="route('assets.show', $asset)">
                        Cancel
                    </x-button>
                    <x-button variant="primary" type="submit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Confirm Move Asset</span>
                    </x-button>
                </div>
            </div>
        </form>
    </div>
@endsection
