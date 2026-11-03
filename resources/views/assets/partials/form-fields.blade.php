@if ($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-950" role="alert" tabindex="-1" aria-labelledby="form-error-summary-title">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h2 id="form-error-summary-title" class="text-sm font-semibold text-red-900">Please review the highlighted fields</h2>
                <ul class="mt-1.5 list-disc list-inside text-xs text-red-800 space-y-1">
                    @foreach ($errors->messages() as $field => $messages)
                        @foreach ($messages as $message)
                            <li>
                                <a href="#{{ $field }}" class="underline hover:text-red-950">{{ $message }}</a>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<div class="space-y-6">
    <!-- Section 1: Identification -->
    <x-card title="Identification" subtitle="Tag code, equipment name, manufacturer model, and serial number">
        <div class="space-y-5">
            <x-form-field
                label="Asset No."
                name="asset_no"
                :value="$asset->asset_no ?? request('asset_no', '')"
                :placeholder="$suggestedAssetNo ?? 'Auto-generate (e.g. BBH-000001)'"
                :help="isset($asset)
                    ? 'Current property tag code: ' . $asset->asset_no . ' (printed on the property tag)'
                    : 'Leave blank to assign ' . ($suggestedAssetNo ?? 'BBH-000001') . ' automatically, or enter an existing hospital paper tag code (e.g. BBHIND-S300002315)'"
                :mono="true"
            >
                @if (! isset($asset) && ! empty($suggestedAssetNo))
                    <x-slot:labelRight>
                        <button type="button"
                                onclick="document.getElementById('asset_no').value='{{ addslashes($suggestedAssetNo) }}';"
                                class="min-h-[32px] px-2.5 py-1 rounded-md border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-700 font-mono text-xs font-medium cursor-pointer transition-colors">
                            Use {{ $suggestedAssetNo }}
                        </button>
                    </x-slot:labelRight>
                @endif
            </x-form-field>

            <x-form-field
                label="Asset Name"
                name="asset_name"
                :required="true"
                :value="$asset->asset_name ?? ''"
                placeholder="Patient Monitor, Hospital Bed, Wheelchair, Defibrillator"
                help="Clear descriptive name printed on the property tag and register"
            />

            <x-form-field
                label="Model / Brand"
                name="model"
                :value="$asset->model ?? ''"
                placeholder="Mindray uMEC10, Philips HeartStart, Samsung RT29"
                help="Manufacturer brand and model designation"
            />

            <x-form-field
                label="Serial No."
                name="serial_no"
                :value="$asset->serial_no ?? ''"
                placeholder="SN-998877"
                help="Manufacturer serial number stamped on the equipment chassis; leave blank if none"
                :mono="true"
            />
        </div>
    </x-card>

    <!-- Section 2: Location & Status -->
    <x-card title="Location" subtitle="Assigned hospital department, room or station, and current operating status">
        <div class="space-y-5">
            <div>
                <x-form-field
                    label="Department"
                    name="department"
                    list="department-options"
                    :value="$asset->department ?? ''"
                    placeholder="Nursing Station 3, ER, ICU, Pharmacy, Laboratory"
                    help="Select an existing hospital unit or type a new department name"
                />
                <datalist id="department-options">
                    @foreach ($departments ?? [] as $dept)
                        <option value="{{ $dept }}"></option>
                    @endforeach
                </datalist>
            </div>

            <x-form-field
                label="Location"
                name="location"
                :value="$asset->location ?? ''"
                placeholder="ND-Station 3 Room 39"
                help="Specific ward, room number, bay, or storage area printed on the property tag"
            />

            <x-form-field
                label="Status"
                name="status"
                type="select"
                :required="true"
                help="Current operational state of this equipment"
            >
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}" @selected(old('status', $asset->status ?? 'In Use') === $statusOption)>
                        {{ $statusOption }}
                    </option>
                @endforeach
            </x-form-field>
        </div>
    </x-card>

    <!-- Section 3: Details -->
    <x-card title="Details" subtitle="Acquisition date and biomedical or inventory remarks">
        <div class="space-y-5">
            <x-form-field
                label="Date Acquired"
                name="date_acquired"
                type="date"
                :value="isset($asset) && $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : ''"
                help="Date the item was received or commissioned by the hospital"
            />

            <x-form-field
                label="Notes / Remarks"
                name="notes"
                type="textarea"
                :value="$asset->notes ?? ''"
                placeholder="Condition notes, supplier details, calibration schedule, or warranty reference"
                help="Internal remarks visible on the asset detail record"
            />
        </div>
    </x-card>
</div>
