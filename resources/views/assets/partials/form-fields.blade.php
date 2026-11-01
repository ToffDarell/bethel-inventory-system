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

<div class="space-y-6">
    <div class="p-4 rounded-xl bg-red-50 border border-red-200">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <label for="asset_no" class="block text-base font-bold text-red-900">
                Asset No. (Leave blank to auto-generate)
            </label>
            @if (! isset($asset) && ! empty($suggestedAssetNo))
                <button type="button"
                        onclick="document.getElementById('asset_no').value='{{ addslashes($suggestedAssetNo) }}';"
                        class="px-3 py-1 rounded-lg bg-red-600 hover:bg-red-700 text-white font-mono font-bold text-xs cursor-pointer transition">
                    Use {{ $suggestedAssetNo }}
                </button>
            @endif
        </div>
        <p class="text-sm text-neutral-700 mb-2.5">
            @if (isset($asset))
                Current Asset No.: <span class="font-mono font-bold text-red-600">{{ $asset->asset_no }}</span>
            @else
                Next automatic number: <span class="font-mono font-bold text-red-600">{{ $suggestedAssetNo ?? 'BBH-000001' }}</span> (or type an existing paper tag number like <span class="font-mono font-semibold">BBHIND-S300002315</span>).
            @endif
        </p>
        <input id="asset_no"
               name="asset_no"
               type="text"
               value="{{ old('asset_no', $asset->asset_no ?? request('asset_no', '')) }}"
               placeholder="{{ $suggestedAssetNo ?? 'Auto-generate (e.g. BBH-000001)' }}"
               class="w-full px-4 py-3 text-lg font-mono font-bold bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
        @error('asset_no')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="asset_name" class="block text-base font-bold text-neutral-900 mb-2">
            Asset Name <span class="text-red-600">*</span>
        </label>
        <input id="asset_name"
               name="asset_name"
               type="text"
               value="{{ old('asset_name', $asset->asset_name ?? '') }}"
               required
               placeholder="e.g. Patient Monitor, Hospital Bed, Wheelchair, Refrigerator"
               class="w-full px-4 py-3 text-lg bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
        @error('asset_name')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="model" class="block text-base font-bold text-neutral-900 mb-2">
            Model / Brand (Optional)
        </label>
        <input id="model"
               name="model"
               type="text"
               value="{{ old('model', $asset->model ?? '') }}"
               placeholder="e.g. Mindray uMEC10, Samsung RT29"
               class="w-full px-4 py-3 text-lg bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
        @error('model')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="serial_no" class="block text-base font-bold text-neutral-900 mb-2">
            Serial No. (Optional)
        </label>
        <input id="serial_no"
               name="serial_no"
               type="text"
               value="{{ old('serial_no', $asset->serial_no ?? '') }}"
               placeholder="Leave blank if the item has no serial number"
               class="w-full px-4 py-3 text-lg font-mono bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
        @error('serial_no')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="date_acquired" class="block text-base font-bold text-neutral-900 mb-2">
            Date Acquired (Optional)
        </label>
        <input id="date_acquired"
               name="date_acquired"
               type="date"
               value="{{ old('date_acquired', isset($asset) && $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : '') }}"
               class="w-full px-4 py-3 text-lg bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
        @error('date_acquired')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="department" class="block text-base font-bold text-neutral-900 mb-2">
            Department (Optional)
        </label>
        <input id="department"
               name="department"
               type="text"
               list="department-options"
               value="{{ old('department', $asset->department ?? '') }}"
               placeholder="e.g. Nursing Station 3, ER, ICU, Pharmacy, Laboratory"
               class="w-full px-4 py-3 text-lg bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
        <datalist id="department-options">
            @foreach ($departments ?? [] as $dept)
                <option value="{{ $dept }}"></option>
            @endforeach
        </datalist>
        @error('department')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="location" class="block text-base font-bold text-neutral-900 mb-2">
            Location (Optional)
        </label>
        <input id="location"
               name="location"
               type="text"
               value="{{ old('location', $asset->location ?? '') }}"
               placeholder="e.g. ND-Station 3 Room 39"
               class="w-full px-4 py-3 text-lg bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">
        @error('location')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-base font-bold text-neutral-900 mb-2">
            Status <span class="text-red-600">*</span>
        </label>
        <select id="status"
                name="status"
                required
                class="w-full px-4 py-3 text-lg border-2 border-red-200 rounded-xl bg-white focus:outline-none focus:border-red-600 transition">
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption }}" @selected(old('status', $asset->status ?? 'In Use') === $statusOption)>
                    {{ $statusOption }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="notes" class="block text-base font-bold text-neutral-900 mb-2">
            Notes / Remarks (Optional)
        </label>
        <textarea id="notes"
                  name="notes"
                  rows="3"
                  placeholder="Any extra details about condition, supplier, or warranty..."
                  class="w-full px-4 py-3 text-lg bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:border-red-600 transition">{{ old('notes', $asset->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1.5 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
