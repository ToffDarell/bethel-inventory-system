@extends('layouts.app')

@section('content')
    <div class="max-w-3xl">
        <x-page-header
            title="Add Asset"
            subtitle="Register hospital equipment into the property inventory. Fields marked with * are required"
            :backUrl="route('assets.index')"
            backLabel="Back to Assets"
        />

        <form method="POST" action="{{ route('assets.store') }}" class="space-y-6">
            @csrf

            @include('assets.partials.form-fields')

            <!-- Sticky Save & Cancel Bar -->
            <div class="sticky bottom-0 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-xl p-4 shadow-2xs flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    A printable property tag will be ready immediately after saving
                </p>
                <div class="flex items-center gap-3">
                    <x-button variant="secondary" :href="route('assets.index')">
                        Cancel
                    </x-button>
                    <x-button variant="primary" type="submit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save Asset</span>
                    </x-button>
                </div>
            </div>
        </form>
    </div>
@endsection
