@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">Add Asset</h2>
                <p class="text-base text-slate-600 mt-1">
                    Register a hospital property item. Fields marked with <span class="text-red-600 font-bold">*</span> are required.
                </p>
            </div>
            <a href="{{ route('assets.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-base">
                <span>Back to Assets</span>
            </a>
        </div>

        <form method="POST" action="{{ route('assets.store') }}" class="p-8 bg-white border-2 border-slate-200 rounded-xl shadow-sm">
            @csrf

            @include('assets.partials.form-fields')

            <div class="mt-8 pt-6 border-t border-slate-200 flex items-center gap-4">
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Save Asset</span>
                </button>
                <a href="{{ route('assets.index') }}"
                   class="px-6 py-4 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
