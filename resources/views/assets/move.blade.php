@extends('layouts.app')

@section('content')
    <div class="max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('assets.show', $asset) }}" class="inline-flex items-center gap-1.5 text-base font-bold text-red-600 hover:underline mb-2">
                &larr; Back to {{ $asset->asset_no }}
            </a>
            <h2 class="text-3xl font-extrabold text-red-600">Move Asset</h2>
            <p class="text-base text-neutral-600 mt-1">
                Update the location of <strong>{{ $asset->asset_name }}</strong> (<span class="font-mono font-bold text-red-600">{{ $asset->asset_no }}</span>) and record the transfer in its history.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border-2 border-red-600 text-red-900" role="alert">
                <p class="font-bold text-base mb-1">Please check the form below:</p>
                <ul class="list-disc list-inside text-base space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('assets.move.store', $asset) }}" class="p-6 bg-white border-2 border-red-100 rounded-2xl space-y-6 shadow-xs">
            @csrf

            <div class="p-4 rounded-xl bg-red-50 text-red-900 border-l-4 border-red-600">
                <p class="text-xs font-extrabold uppercase tracking-wider text-red-600">Current Location</p>
                <p class="text-lg font-bold text-red-900 mt-1">
                    {{ $asset->location ?: 'Not assigned yet' }}
                </p>
                @if ($asset->department)
                    <p class="text-sm text-red-800 mt-0.5">
                        Current Department: <strong class="text-red-900">{{ $asset->department }}</strong>
                    </p>
                @endif
            </div>

            <div>
                <label for="to_location" class="block text-base font-bold text-neutral-900 mb-2">
                    New Location <span class="text-red-600">*</span>
                </label>
                <input id="to_location"
                       name="to_location"
                       type="text"
                       required
                       autofocus
                       value="{{ old('to_location') }}"
                       placeholder="e.g. ND-Station 3 Room 39"
                       class="w-full px-4 py-3 text-lg border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
            </div>

            <div>
                <label for="department" class="block text-base font-bold text-neutral-900 mb-2">
                    Department (Optional)
                </label>
                <input id="department"
                       name="department"
                       type="text"
                       list="move-department-options"
                       value="{{ old('department', $asset->department) }}"
                       placeholder="e.g. Nursing Station 3, ER, ICU, Pharmacy"
                       class="w-full px-4 py-3 text-lg border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
                <datalist id="move-department-options">
                    @foreach ($departments ?? [] as $dept)
                        <option value="{{ $dept }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="notes" class="block text-base font-bold text-neutral-900 mb-2">
                    Reason / Notes (Optional)
                </label>
                <textarea id="notes"
                          name="notes"
                          rows="3"
                          placeholder="e.g. Transferred for patient admission in Room 39"
                          class="w-full px-4 py-3 text-lg border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-red-100 flex items-center gap-4">
                <button type="submit"
                        class="px-6 py-3.5 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-lg shadow-xs transition cursor-pointer">
                    Confirm Move Asset
                </button>
                <a href="{{ route('assets.show', $asset) }}"
                   class="px-5 py-3.5 rounded-xl border-2 border-red-200 bg-white hover:bg-red-50 text-red-700 font-bold text-base transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
