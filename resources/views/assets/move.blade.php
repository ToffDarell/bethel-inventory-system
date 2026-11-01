@extends('layouts.app')

@section('content')
    <div class="max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('assets.show', $asset) }}" class="inline-flex items-center gap-1.5 text-base font-bold text-blue-700 hover:underline mb-2">
                &larr; Back to {{ $asset->asset_no }}
            </a>
            <h2 class="text-3xl font-bold text-slate-900">Move Asset</h2>
            <p class="text-base text-slate-600 mt-1">
                Update the location of <strong>{{ $asset->asset_name }}</strong> (<span class="font-mono font-bold">{{ $asset->asset_no }}</span>) and record the transfer in its history.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border-2 border-red-400 text-red-900" role="alert">
                <p class="font-bold text-base mb-1">Please check the form below:</p>
                <ul class="list-disc list-inside text-base space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('assets.move.store', $asset) }}" class="p-6 bg-white border-2 border-slate-200 rounded-xl space-y-6">
            @csrf

            <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                <p class="text-sm font-bold uppercase tracking-wide text-slate-500">Current Location</p>
                <p class="text-lg font-bold text-slate-900 mt-1">
                    {{ $asset->location ?: 'Not assigned yet' }}
                </p>
                @if ($asset->department)
                    <p class="text-sm text-slate-600 mt-0.5">
                        Current Department: <strong>{{ $asset->department }}</strong>
                    </p>
                @endif
            </div>

            <div>
                <label for="to_location" class="block text-base font-bold text-slate-900 mb-2">
                    New Location <span class="text-red-600">*</span>
                </label>
                <input id="to_location"
                       name="to_location"
                       type="text"
                       required
                       autofocus
                       value="{{ old('to_location') }}"
                       placeholder="e.g. ND-Station 3 Room 39"
                       class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>

            <div>
                <label for="department" class="block text-base font-bold text-slate-900 mb-2">
                    Department (Optional)
                </label>
                <input id="department"
                       name="department"
                       type="text"
                       list="move-department-options"
                       value="{{ old('department', $asset->department) }}"
                       placeholder="e.g. Nursing Station 3, ER, ICU, Pharmacy"
                       class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                <datalist id="move-department-options">
                    @foreach ($departments ?? [] as $dept)
                        <option value="{{ $dept }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="notes" class="block text-base font-bold text-slate-900 mb-2">
                    Reason / Notes (Optional)
                </label>
                <textarea id="notes"
                          name="notes"
                          rows="3"
                          placeholder="e.g. Transferred for patient admission in Room 39"
                          class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center gap-4">
                <button type="submit"
                        class="px-6 py-3.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                    Confirm Move Asset
                </button>
                <a href="{{ route('assets.show', $asset) }}"
                   class="px-5 py-3.5 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-base transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
