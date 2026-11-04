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

    <!-- Section: Category and Value -->
    <x-card title="Category and Value" subtitle="Equipment classification and straight-line depreciation parameters">
        <div class="space-y-5">
            <div>
                <label for="category_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                    Category
                </label>
                <select id="category_id"
                        name="category_id"
                        onchange="handleCategoryChange(this)"
                        class="w-full min-h-[44px] px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="" data-life="" data-salvage="0">Uncategorized (Select a category)</option>
                    @foreach ($categories ?? [] as $categoryOption)
                        <option value="{{ $categoryOption->id }}"
                                data-life="{{ $categoryOption->default_useful_life_years ?? '' }}"
                                data-salvage="{{ $categoryOption->default_salvage_percent ?? 0 }}"
                                @selected(old('category_id', $asset->category_id ?? '') == $categoryOption->id)>
                            {{ $categoryOption->name }}
                            @if ($categoryOption->default_useful_life_years)
                                ({{ $categoryOption->default_useful_life_years }} yrs)
                            @endif
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Choosing a category automatically fills recommended useful life and salvage value</p>
            </div>

            @if (auth()->user()?->isAdmin())
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-100">
                    <div>
                        <label for="cost" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Cost (₱)
                        </label>
                        <div class="relative rounded-lg">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-semibold text-sm">
                                ₱
                            </div>
                            <input type="number"
                                   id="cost"
                                   name="cost"
                                   step="0.01"
                                   min="0"
                                   oninput="updateDepreciationPreview()"
                                   value="{{ old('cost', isset($asset) && $asset->cost !== null ? $asset->cost : '') }}"
                                   placeholder="0.00"
                                   class="w-full min-h-[44px] pl-8 pr-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Original acquisition cost</p>
                    </div>

                    <div>
                        <label for="useful_life_years" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Useful life (years)
                        </label>
                        <input type="number"
                               id="useful_life_years"
                               name="useful_life_years"
                               min="1"
                               max="100"
                               oninput="updateDepreciationPreview()"
                               value="{{ old('useful_life_years', $asset->useful_life_years ?? '') }}"
                               placeholder="e.g. 10"
                               class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <p class="text-[11px] text-slate-500 mt-1">Depreciable lifespan</p>
                    </div>

                    <div>
                        <label for="salvage_value" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Salvage value (₱)
                        </label>
                        <div class="relative rounded-lg">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-semibold text-sm">
                                ₱
                            </div>
                            <input type="number"
                                   id="salvage_value"
                                   name="salvage_value"
                                   step="0.01"
                                   min="0"
                                   oninput="updateDepreciationPreview()"
                                   value="{{ old('salvage_value', isset($asset) && $asset->salvage_value !== null ? $asset->salvage_value : '') }}"
                                   placeholder="0.00"
                                   class="w-full min-h-[44px] pl-8 pr-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Estimated residual value at end of life</p>
                    </div>
                </div>

                <!-- Live Preview Line -->
                <div id="depreciation-live-preview" class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <span class="text-slate-600 font-medium">Estimated current value:</span>
                    <strong id="preview-value-text" class="text-sm font-bold font-mono text-slate-900">No cost entered</strong>
                </div>
            @endif
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

@if (auth()->user()?->isAdmin())
<script>
    function handleCategoryChange(select) {
        const option = select.options[select.selectedIndex];
        if (!option) return;

        const life = option.dataset.life;
        const salvagePercent = parseFloat(option.dataset.salvage || 0);

        const lifeInput = document.getElementById('useful_life_years');
        const costInput = document.getElementById('cost');
        const salvageInput = document.getElementById('salvage_value');

        if (life && lifeInput && (!lifeInput.value || lifeInput.value === '')) {
            lifeInput.value = life;
        }

        if (salvagePercent > 0 && costInput && costInput.value && salvageInput && (!salvageInput.value || salvageInput.value === '0')) {
            const cost = parseFloat(costInput.value);
            if (!isNaN(cost) && cost > 0) {
                salvageInput.value = (cost * (salvagePercent / 100)).toFixed(2);
            }
        }

        updateDepreciationPreview();
    }

    function updateDepreciationPreview() {
        const previewText = document.getElementById('preview-value-text');
        if (!previewText) return;

        const costVal = document.getElementById('cost')?.value;
        const lifeVal = document.getElementById('useful_life_years')?.value;
        const salvageVal = document.getElementById('salvage_value')?.value || '0';
        const dateVal = document.getElementById('date_acquired')?.value;

        if (!costVal || isNaN(parseFloat(costVal)) || parseFloat(costVal) <= 0) {
            previewText.textContent = 'No cost entered';
            return;
        }

        const cost = parseFloat(costVal);
        const salvage = Math.max(0, parseFloat(salvageVal) || 0);
        const usefulLife = parseInt(lifeVal, 10);

        if (!dateVal || isNaN(usefulLife) || usefulLife <= 0) {
            previewText.textContent = 'Not enough data (requires cost, date acquired, and useful life)';
            return;
        }

        const acquiredDate = new Date(dateVal);
        const now = new Date();

        if (acquiredDate > now) {
            previewText.textContent = '₱' + cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (no depreciation yet)';
            return;
        }

        const depreciable = Math.max(0, cost - salvage);
        const totalMonths = Math.max(1, usefulLife * 12);
        const monthlyRate = depreciable / totalMonths;

        let monthsElapsed = (now.getFullYear() - acquiredDate.getFullYear()) * 12 + (now.getMonth() - acquiredDate.getMonth());
        if (now.getDate() < acquiredDate.getDate()) {
            monthsElapsed--;
        }
        monthsElapsed = Math.max(0, monthsElapsed);

        let accumulated = 0;
        let current = cost;

        if (monthsElapsed >= totalMonths) {
            accumulated = depreciable;
            current = salvage;
        } else {
            accumulated = Math.min(depreciable, monthlyRate * monthsElapsed);
            current = Math.max(salvage, cost - accumulated);
        }

        previewText.textContent = '₱' + current.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('date_acquired');
        if (dateInput) {
            dateInput.addEventListener('change', updateDepreciationPreview);
        }
        updateDepreciationPreview();
    });
</script>
@endif
