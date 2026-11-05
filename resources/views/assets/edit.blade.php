@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <x-page-header
            title="Edit Asset"
            :subtitle="'Updating ' . $asset->asset_no . ': ' . $asset->asset_name"
            :backUrl="route('assets.show', $asset)"
            backLabel="Back to Asset"
        />

        <form method="POST" action="{{ route('assets.update', $asset) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('assets.partials.form-fields', ['asset' => $asset])

            <!-- Sticky Save & Cancel Bar -->
            <div class="sticky bottom-0 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-xl p-4 shadow-2xs flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    Changes update the register and property tag immediately
                </p>
                <div class="flex items-center gap-3">
                    <x-button variant="secondary" :href="route('assets.show', $asset)">
                        Cancel
                    </x-button>
                    <x-button variant="primary" type="submit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save Changes</span>
                    </x-button>
                </div>
            </div>
        </form>
    </div>
@endsection
