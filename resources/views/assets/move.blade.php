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
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-slate-500">Current Location</p>
                            <p class="text-base font-semibold text-slate-900 mt-0.5">
                                {{ $asset->location ?: 'Not assigned yet' }}
                            </p>
                            @if ($asset->department)
                                <p class="text-xs text-slate-600 mt-0.5">
                                    Department: <span class="font-medium text-slate-800">{{ $asset->department }}</span>
                                </p>
                            @endif
                        </div>
                        <span class="font-mono text-xs font-medium text-slate-800 bg-white border border-slate-200 px-2.5 py-1 rounded">
                            {{ $asset->asset_no }}
                        </span>
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
