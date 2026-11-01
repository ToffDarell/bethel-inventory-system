@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">Dashboard</h2>
            <p class="text-base text-slate-600 mt-1">
                {{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }} — Property Asset Overview
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('assets.index') }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-base shadow-sm transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Asset</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="p-6 rounded-xl border-2 border-slate-200 bg-white">
            <p class="text-sm font-bold uppercase tracking-wide text-slate-600">Total Assets</p>
            <p class="text-4xl font-extrabold text-blue-700 mt-2">{{ $totalAssets ?? 0 }}</p>
        </div>
        @foreach (\App\Models\Asset::STATUSES as $status)
            <div class="p-6 rounded-xl border-2 border-slate-200 bg-white">
                <p class="text-sm font-bold uppercase tracking-wide text-slate-600">{{ $status }}</p>
                <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $statusCounts[$status] ?? 0 }}</p>
            </div>
        @endforeach
    </div>
@endsection
