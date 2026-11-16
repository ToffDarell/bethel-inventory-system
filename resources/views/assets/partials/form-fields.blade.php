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

@if (isset($asset) && $asset->exists)
    <input type="hidden" name="lock_version" value="{{ $asset->updated_at?->timestamp }}">
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    <!-- Left Column: Identification, Condition & Status, Remarks -->
    <div class="space-y-6">
        <!-- 1. Identification Card -->
        <x-card title="Identification" subtitle="Tag code, equipment description, brand, model, and serial number">
            <div class="space-y-5">
                <!-- Masterlist Template Picker -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5" id="masterlist-picker-box">
                    <input type="hidden" id="asset_template_id" name="asset_template_id" value="{{ old('asset_template_id', $asset->asset_template_id ?? '') }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label for="masterlist_search" class="text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Select from masterlist <span class="font-normal normal-case text-slate-500">(Optional)</span>
                        </label>
                        @if (auth()->user()?->isAdmin())
                            <button type="button"
                                    id="toggle-inline-masterlist-btn"
                                    onclick="toggleInlineMasterlistForm()"
                                    class="text-xs font-semibold text-primary hover:underline cursor-pointer">
                                + Add to masterlist
                            </button>
                        @endif
                    </div>

                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <input type="search"
                                   id="masterlist_search"
                                   autocomplete="off"
                                   value="{{ isset($selectedTemplate) && $selectedTemplate ? trim($selectedTemplate->name . ($selectedTemplate->brand ? ' - ' . $selectedTemplate->brand : '') . ($selectedTemplate->model ? ' ' . $selectedTemplate->model : '')) : '' }}"
                                   placeholder="Type equipment name, brand, or model to auto-fill..."
                                   class="w-full min-h-[40px] px-3 py-1.5 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <button type="button"
                                    id="masterlist_clear_btn"
                                    onclick="clearSelectedMasterlist()"
                                    class="min-h-[40px] px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 cursor-pointer {{ old('asset_template_id', $asset->asset_template_id ?? '') ? '' : 'hidden' }}">
                                Clear
                            </button>
                        </div>

                        <div id="masterlist_results"
                             class="hidden absolute left-0 right-0 z-30 mt-1 max-h-60 overflow-y-auto bg-white border border-slate-300 rounded-xl shadow-lg divide-y divide-slate-100">
                        </div>
                    </div>

                    <p id="masterlist_selected_badge" class="text-[11px] text-slate-600 {{ isset($selectedTemplate) && $selectedTemplate ? '' : 'hidden' }}">
                        @if (isset($selectedTemplate) && $selectedTemplate)
                            Selected masterlist item: <strong class="font-semibold text-slate-800">{{ $selectedTemplate->name }}</strong>
                            @if ($selectedTemplate->category)
                                ({{ $selectedTemplate->category->name }})
                            @endif
                            @if (! $selectedTemplate->active)
                                <span class="text-amber-700 font-medium">(Inactive template retained)</span>
                            @endif
                        @endif
                    </p>

                    <div id="masterlist_replace_confirm" class="hidden p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-950 space-y-2" role="region" aria-label="Confirm overwrite">
                        <p class="font-semibold">Replace the current values?</p>
                        <p class="text-amber-800">Some equipment fields already contain text. Applying <span id="masterlist_pending_label" class="font-semibold"></span> will update Description, Brand, Model, Category, and Remarks.</p>
                        <div class="flex items-center gap-2 pt-0.5">
                            <button type="button" id="masterlist_confirm_replace_btn" onclick="confirmPendingTemplateReplace()" class="px-3 py-1.5 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg cursor-pointer">
                                Replace Current Values
                            </button>
                            <button type="button" id="masterlist_cancel_replace_btn" onclick="cancelPendingTemplateReplace()" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">
                                Keep Current
                            </button>
                        </div>
                    </div>

                    @if (auth()->user()?->isAdmin())
                        <div id="inline-masterlist-create-panel" class="hidden p-3.5 rounded-lg bg-white border border-slate-300 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">Save New Masterlist Template</span>
                                <button type="button" onclick="toggleInlineMasterlistForm(false)" class="text-xs text-slate-500 hover:text-slate-800 cursor-pointer">Close</button>
                            </div>
                            <div id="inline_tpl_error" class="hidden p-2 rounded bg-red-50 border border-red-200 text-xs text-red-800"></div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="sm:col-span-2">
                                    <label for="inline_tpl_name" class="block text-[11px] font-semibold text-slate-700 mb-0.5">Equipment Name <span class="text-brand-red">*</span></label>
                                    <input type="text" id="inline_tpl_name" class="w-full min-h-[36px] px-2.5 py-1 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label for="inline_tpl_brand" class="block text-[11px] font-semibold text-slate-700 mb-0.5">Brand</label>
                                    <input type="text" id="inline_tpl_brand" class="w-full min-h-[36px] px-2.5 py-1 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label for="inline_tpl_model" class="block text-[11px] font-semibold text-slate-700 mb-0.5">Model</label>
                                    <input type="text" id="inline_tpl_model" class="w-full min-h-[36px] px-2.5 py-1 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="inline_tpl_category_id" class="block text-[11px] font-semibold text-slate-700 mb-0.5">Default Category</label>
                                    <select id="inline_tpl_category_id" class="w-full min-h-[36px] px-2.5 py-1 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">-- Blank --</option>
                                        @foreach ($categories ?? [] as $catOption)
                                            <option value="{{ $catOption->id }}">{{ $catOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="inline_tpl_remarks" class="block text-[11px] font-semibold text-slate-700 mb-0.5">Default Remarks</label>
                                    <input type="text" id="inline_tpl_remarks" class="w-full min-h-[36px] px-2.5 py-1 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="toggleInlineMasterlistForm(false)" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">Cancel</button>
                                <button type="button" id="inline_tpl_save_btn" onclick="submitInlineMasterlistTemplate()" class="px-3 py-1.5 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg cursor-pointer">Save Template</button>
                            </div>
                        </div>
                    @endif
                </div>

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
                                    id="use-suggested-asset-no-btn"
                                    data-fallback-no="{{ $suggestedAssetNo }}"
                                    onclick="document.getElementById('asset_no').value=this.dataset.previewNo || this.dataset.fallbackNo;"
                                    class="min-h-[32px] px-2.5 py-1 rounded-md border border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-700 font-mono text-xs font-medium cursor-pointer transition-colors">
                                Use {{ $suggestedAssetNo }}
                            </button>
                        </x-slot:labelRight>
                    @endif
                </x-form-field>

                @if (isset($asset) && $asset->exists && !app(\App\Services\AssetNumberService::class)->matchesPattern($asset->asset_no, $asset->departmentRelation) && auth()->user()?->isAdmin())
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-2">
                        <div class="font-semibold text-slate-800">Legacy / Paper Tag Number Detected</div>
                        <p class="text-slate-600">This asset uses non-standard numbering (<span class="font-mono font-semibold">{{ $asset->asset_no }}</span>). If changing department, choose whether to keep or assign a new automated sequence:</p>
                        <div class="flex items-center gap-4 pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                <input type="radio" name="paper_tag_number_action" value="keep" checked class="text-primary focus:ring-primary">
                                Keep current number
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                <input type="radio" name="paper_tag_number_action" value="assign_new" class="text-primary focus:ring-primary">
                                Assign new department number
                            </label>
                        </div>
                    </div>
                @endif

                <x-form-field
                    label="Asset Description"
                    name="asset_name"
                    :required="true"
                    :value="$asset->asset_name ?? ''"
                    placeholder="Patient Monitor, Hospital Bed, Wheelchair, Defibrillator"
                    help="Clear descriptive name printed on the property tag and register"
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                    <x-form-field
                        label="Brand"
                        name="brand"
                        :value="$asset->brand ?? ''"
                        placeholder="Philips, Mindray, Samsung"
                        help="Manufacturer brand name"
                    />

                    <x-form-field
                        label="Model"
                        name="model"
                        :value="$asset->model ?? ''"
                        placeholder="HeartStart MRx, RT29"
                        help="Manufacturer model designation"
                    />
                </div>

                <!-- Optional Vendor / Supplier Searchable Combobox -->
                @php
                    $selectedVendorId = (string) old('vendor_id', $asset->vendor_id ?? '');
                    $selectedVendorObj = collect($vendors ?? [])->first(fn ($v) => (string) $v->id === $selectedVendorId);
                    $selectedVendorLabel = $selectedVendorObj
                        ? ($selectedVendorObj->name . ($selectedVendorObj->is_donation ? ' (Donation)' : '') . (! $selectedVendorObj->active ? ' [Inactive]' : ''))
                        : '';
                @endphp
                <div id="vendor-combobox-container">
                    <label for="vendor_search_input" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                        Vendor / Supplier <span class="font-normal normal-case text-slate-500">(Optional)</span>
                    </label>

                    {{-- Hidden native select keeps form submission & fallback semantics --}}
                    <select id="vendor_id" name="vendor_id" class="hidden" aria-hidden="true" tabindex="-1">
                        <option value="">-- Optional: Select Vendor --</option>
                        @foreach ($vendors ?? [] as $vendorOption)
                            <option value="{{ $vendorOption->id }}"
                                    data-name="{{ $vendorOption->name }}"
                                    data-donation="{{ $vendorOption->is_donation ? '1' : '0' }}"
                                    data-active="{{ $vendorOption->active ? '1' : '0' }}"
                                    @selected($selectedVendorId === (string) $vendorOption->id)>
                                {{ $vendorOption->name }}{{ $vendorOption->is_donation ? ' (Donation)' : '' }}{{ ! $vendorOption->active ? ' [Inactive]' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input type="text"
                                       id="vendor_search_input"
                                       autocomplete="off"
                                       value="{{ $selectedVendorLabel }}"
                                       placeholder="Type or click to search vendor or donation source..."
                                       aria-label="Search or select vendor"
                                       aria-expanded="false"
                                       aria-controls="vendor_results_popover"
                                       class="w-full min-h-[44px] pl-3.5 pr-9 py-2.5 text-sm bg-white border {{ $errors->has('vendor_id') ? 'border-red-500 focus:border-red-600 focus:ring-red-600' : 'border-slate-300 focus:border-primary' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <button type="button"
                                        id="vendor_dropdown_toggle_btn"
                                        tabindex="-1"
                                        aria-label="Toggle vendor list"
                                        class="absolute inset-y-0 right-0 w-9 flex items-center justify-center text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>
                            <button type="button"
                                    id="vendor_clear_btn"
                                    class="min-h-[44px] px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 cursor-pointer {{ $selectedVendorId !== '' ? '' : 'hidden' }}">
                                Clear
                            </button>
                        </div>

                        <div id="vendor_results_popover"
                             role="listbox"
                             class="hidden absolute left-0 right-0 z-30 mt-1 max-h-60 overflow-y-auto bg-white border border-slate-300 rounded-xl shadow-lg divide-y divide-slate-100">
                        </div>
                    </div>

                    <p id="vendor_inline_feedback" class="hidden text-xs font-medium mt-1"></p>
                    @error('vendor_id')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-slate-500 mt-1">Click or type to search active vendors and donation sources. Leave blank if unknown.</p>
                </div>

                <x-form-field
                    label="Serial Number"
                    name="serial_no"
                    :value="$asset->serial_no ?? ''"
                    placeholder="SN-998877"
                    help="Manufacturer serial number stamped on the equipment chassis; leave blank if none"
                    :mono="true"
                />

                <div>
                    <label for="category_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                        Category
                    </label>
                    <select id="category_id"
                            name="category_id"
                            onchange="handleCategoryChange(this)"
                            class="w-full min-h-[44px] px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="" data-life="" data-disposal="" data-salvage="0">Uncategorized (Select a category)</option>
                        @foreach ($categories ?? [] as $categoryOption)
                            <option value="{{ $categoryOption->id }}"
                                    data-life="{{ $categoryOption->default_useful_life_years ?? '' }}"
                                    data-disposal="{{ $categoryOption->default_salvage_percent ?? '' }}"
                                    data-salvage="{{ $categoryOption->default_salvage_percent ?? 0 }}"
                                    @selected(old('category_id', $asset->category_id ?? '') == $categoryOption->id)>
                                {{ $categoryOption->name }}
                                @if ($categoryOption->default_useful_life_years)
                                    ({{ $categoryOption->default_useful_life_years }} yrs)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Choosing a category automatically fills recommended lifespan and ending value</p>
                </div>
            </div>
        </x-card>

        <!-- 3. Condition and Status Card -->
        <x-card title="Condition and Status" subtitle="Operational status and physical condition">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                <x-form-field
                    label="Status"
                    name="status"
                    type="select"
                    :required="true"
                    help="In use, stored, or being repaired?"
                >
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected(old('status', $asset->status ?? 'In Use') === $statusOption)>
                            {{ $statusOption }}
                        </option>
                    @endforeach
                </x-form-field>

                <x-form-field
                    label="Asset Condition"
                    name="asset_condition"
                    type="select"
                    :required="true"
                    help="Physical state of the equipment"
                >
                    <option value="" disabled @selected(!old('asset_condition', $asset->asset_condition ?? ''))>
                        -- Select Asset Condition --
                    </option>
                    @foreach ($conditions as $conditionOption)
                        <option value="{{ $conditionOption }}" @selected(old('asset_condition', $asset->asset_condition ?? '') === $conditionOption)>
                            {{ $conditionOption }}
                        </option>
                    @endforeach
                </x-form-field>
            </div>
        </x-card>

        <!-- 5. Remarks Card -->
        <x-card title="Remarks" subtitle="Biomedical notes, warranty references, or supplier remarks">
            <div class="space-y-5">
                <x-form-field
                    label="Remarks"
                    name="notes"
                    type="textarea"
                    :value="$asset->notes ?? ''"
                    placeholder="Condition notes, supplier details, calibration schedule, or warranty reference"
                    help="Internal remarks visible on the asset detail record"
                />
            </div>
        </x-card>
    </div>

    <!-- Right Column: Assignment & Purchase / Financials -->
    <div class="space-y-6">
        <!-- 2. Assignment Card -->
        <x-card title="Assignment" subtitle="Assigned hospital department, section, location, and responsible personnel">
            <div class="space-y-5">
                <div class="space-y-1.5">
                    <label for="department_id" class="block text-sm font-semibold text-slate-800">
                        Department
                        <span class="text-brand-red font-semibold ml-0.5" aria-hidden="true">*</span>
                    </label>
                    <select id="department_id"
                            name="department_id"
                            required
                            aria-invalid="{{ $errors->has('department_id') ? 'true' : 'false' }}"
                            onchange="handleDepartmentChange(this)"
                            class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border {{ $errors->has('department_id') ? 'border-red-500 focus:border-red-600 focus:ring-red-600' : 'border-slate-300 focus:border-slate-900' }} rounded-lg shadow-2xs focus:outline-none transition-colors">
                        <option value="">-- Select Hospital Department --</option>
                        @foreach ($departments ?? [] as $d)
                            @php
                                $dId = is_object($d) ? $d->id : null;
                                $dName = is_object($d) ? $d->name : $d;
                                $dCode = is_object($d) ? $d->code : '';
                                $dConfirmed = is_object($d) ? (bool) $d->code_confirmed : true;
                                $selected = (isset($asset) && $asset->department_id == $dId)
                                    || (old('department_id') == $dId)
                                    || (isset($asset) && !empty($asset->department) && $asset->department === $dName);
                            @endphp
                            <option value="{{ $dId }}"
                                    data-code="{{ $dCode }}"
                                    data-confirmed="{{ $dConfirmed ? '1' : '0' }}"
                                    {{ $selected ? 'selected' : '' }}>
                                {{ $dName }} ({{ $dCode }}){{ !$dConfirmed ? ' [Needs Code Review]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p id="dept-unconfirmed-notice" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mt-1.5">
                        The selected department code is not confirmed. Please confirm the code in <a href="{{ route('settings.index', ['tab' => 'departments']) }}" target="_blank" class="font-semibold underline">Settings &gt; Departments</a> before generating automated numbers.
                    </p>
                    <p class="text-xs text-slate-500 mt-1">Select the responsible hospital department.</p>
                    @error('department_id')
                        <p class="text-xs font-medium text-brand-red flex items-center gap-1.5 mt-1" role="alert">
                            <svg class="w-3.5 h-3.5 shrink-0 text-brand-red" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="section_id" class="block text-sm font-semibold text-slate-800">
                        Section
                        <span class="text-brand-red font-semibold ml-0.5" aria-hidden="true">*</span>
                    </label>
                    <select id="section_id"
                            name="section_id"
                            required
                            aria-invalid="{{ ($errors->has('section_id') || $errors->has('section')) ? 'true' : 'false' }}"
                            class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border {{ ($errors->has('section_id') || $errors->has('section')) ? 'border-red-500 focus:border-red-600 focus:ring-red-600' : 'border-slate-300 focus:border-slate-900' }} rounded-lg shadow-2xs focus:outline-none transition-colors">
                        <option value="">-- Select Section --</option>
                        @foreach ($sectionOptions ?? [] as $sec)
                            @php
                                $selectedSection = old('section_id', $asset->section_id ?? null);
                                $isSecSelected = ($selectedSection !== null && (string) $selectedSection === (string) $sec->id)
                                    || ($selectedSection === null && isset($asset) && !empty($asset->section) && (int) $asset->department_id === (int) $sec->department_id && strtolower(trim($asset->section)) === strtolower(trim($sec->name)));
                            @endphp
                            <option value="{{ $sec->id }}"
                                    data-department-id="{{ $sec->department_id }}"
                                    data-code="{{ $sec->code }}"
                                    data-confirmed="{{ $sec->code_confirmed ? '1' : '0' }}"
                                    {{ $isSecSelected ? 'selected' : '' }}>
                                {{ $sec->name }} ({{ $sec->code }}){{ !$sec->code_confirmed ? ' [Needs Code Review]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p id="section-unconfirmed-notice" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mt-1.5">
                        The selected section code is not confirmed. Please confirm the code in <a href="{{ route('settings.index', ['tab' => 'departments']) }}" target="_blank" class="font-semibold underline">Settings &gt; Departments</a> before generating automated numbers.
                    </p>
                    <p id="section-empty-notice" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mt-1.5">
                        No sections are registered for this department yet. Please add a section in <a href="{{ route('settings.index', ['tab' => 'departments']) }}" target="_blank" class="font-semibold underline">Settings &gt; Departments</a>.
                    </p>
                    <p class="text-xs text-slate-500 mt-1">Select the section inside the chosen department (managed in Settings &gt; Departments).</p>
                    @error('section_id')
                        <p class="text-xs font-medium text-brand-red flex items-center gap-1.5 mt-1" role="alert">
                            <svg class="w-3.5 h-3.5 shrink-0 text-brand-red" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                    @error('section')
                        <p class="text-xs font-medium text-brand-red flex items-center gap-1.5 mt-1" role="alert">
                            <svg class="w-3.5 h-3.5 shrink-0 text-brand-red" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <x-form-field
                    label="Location"
                    name="location"
                    :value="$asset->location ?? ''"
                    placeholder="ND-Station 3 Room 39"
                    help="Specific ward, room number, bay, or storage area printed on the property tag"
                />

                <div>
                    <x-form-field
                        label="Assigned Personnel"
                        name="assigned_personnel"
                        list="personnel-options"
                        :value="$asset->assigned_personnel ?? ''"
                        placeholder="Dr. Cruz, Nurse Joy"
                        help="Name of the person responsible for this item."
                    />
                    <datalist id="personnel-options">
                        @foreach ($assignedPersonnel ?? [] as $person)
                            <option value="{{ $person }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>
        </x-card>

        <!-- 4. Purchase & Financials Card -->
        <x-card title="Purchase & Financials" subtitle="When it was bought, purchase cost, and expected lifespan">
            <div class="space-y-5">
                <div>
                    <label for="year_bought" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                        Year Bought
                    </label>
                    <input type="number"
                           id="year_bought"
                           name="year_bought"
                           min="1950"
                           max="{{ (int) date('Y') }}"
                           placeholder="e.g. {{ date('Y') }}"
                           oninput="updateDepreciationPreview()"
                           value="{{ old('year_bought', isset($asset) && $asset->date_acquired ? $asset->date_acquired->format('Y') : '') }}"
                           class="w-full min-h-[44px] px-3.5 py-2 text-sm font-mono bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-[11px] text-slate-500 mt-1">Year the hospital bought or received this equipment</p>
                </div>

                @if (auth()->user()?->isAdmin())
                    <div class="space-y-4 pt-2 border-t border-slate-100">
                        <div>
                            <label for="cost" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Cost (₱)
                            </label>
                            <div class="relative rounded-lg max-w-md">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-semibold text-sm">
                                    ₱
                                </div>
                                <input type="text"
                                       inputmode="decimal"
                                       id="cost"
                                       name="cost"
                                       oninput="handleCurrencyInput(this)"
                                       value="{{ old('cost', isset($asset) && $asset->cost !== null ? number_format((float) $asset->cost, 2) : '') }}"
                                       placeholder="0.00"
                                       class="w-full min-h-[44px] pl-8 pr-3.5 py-2 text-sm font-mono bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">What the hospital paid to purchase this item</p>
                        </div>

                        <!-- Read-only Category Depreciation Presets -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block">Useful Life</span>
                                <strong id="category-life-display" class="text-sm font-bold text-slate-900 block mt-0.5 tabular-nums">
                                    -
                                </strong>
                                <span class="text-[10px] text-slate-500 block mt-0.5">From category</span>
                            </div>

                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block">Disposal %</span>
                                <strong id="category-disposal-rate-display" class="text-sm font-bold text-slate-900 block mt-0.5 tabular-nums">
                                    -
                                </strong>
                                <span class="text-[10px] text-slate-500 block mt-0.5">From category</span>
                            </div>

                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 block">Disposal Value Floor</span>
                                <strong id="category-disposal-value-display" class="text-sm font-bold font-mono text-slate-900 block mt-0.5 tabular-nums">
                                    -
                                </strong>
                                <span class="text-[10px] text-slate-500 block mt-0.5">Cost &times; Disposal %</span>
                            </div>
                        </div>

                        <!-- Live Preview Line -->
                        <div id="depreciation-live-preview" class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="text-slate-600 font-medium">Estimated current value:</span>
                            <strong id="preview-value-text" class="text-xs font-semibold font-mono text-slate-900">Select a category and enter a cost to calculate</strong>
                        </div>
                    </div>
                @endif
            </div>
        </x-card>
    </div>
</div>

@if (auth()->user()?->isAdmin())
<script>
    function formatWithCommas(val) {
        if (val === null || val === undefined) return '';
        let str = String(val).replace(/,/g, '').trim();
        if (str === '') return '';

        let parts = str.split('.');
        let intPart = parts[0].replace(/\D/g, '');
        let decPart = parts.length > 1 ? '.' + parts[1].replace(/\D/g, '').slice(0, 2) : '';

        if (!intPart && !decPart) return '';
        let formattedInt = intPart ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : (parts.length > 1 ? '0' : '');
        return formattedInt + decPart;
    }

    function getCategoryData() {
        const catSelect = document.getElementById('category_id');
        const option = catSelect ? catSelect.options[catSelect.selectedIndex] : null;
        if (!option || !catSelect.value) {
            return { life: null, disposalPercent: null };
        }
        const life = option.dataset.life ? parseInt(option.dataset.life, 10) : null;
        const rawDisposal = option.dataset.disposal !== undefined && option.dataset.disposal !== '' ? option.dataset.disposal : option.dataset.salvage;
        let disposalPercent = (rawDisposal !== '' && rawDisposal !== undefined) ? parseFloat(rawDisposal) : null;
        if (disposalPercent === null || isNaN(disposalPercent)) {
            const globalFallback = parseFloat('{{ \App\Models\Setting::get('depreciation_default_salvage_percent', '0') }}');
            disposalPercent = !isNaN(globalFallback) ? globalFallback : 0;
        }
        return { life: life, disposalPercent: disposalPercent };
    }

    function handleCurrencyInput(input) {
        const cursor = input.selectionStart;
        const prevVal = input.value;
        const prevCommas = (prevVal.slice(0, cursor).match(/,/g) || []).length;

        const formatted = formatWithCommas(input.value);
        input.value = formatted;

        const newCommas = (formatted.slice(0, cursor).match(/,/g) || []).length;
        const diff = newCommas - prevCommas;
        const newCursor = Math.max(0, cursor + diff);
        input.setSelectionRange(newCursor, newCursor);

        updateDepreciationPreview();
    }

    function handleCategoryChange(select) {
        updateDepreciationPreview();
    }

    function updateDepreciationPreview() {
        const previewText = document.getElementById('preview-value-text');
        const lifeDisplay = document.getElementById('category-life-display');
        const rateDisplay = document.getElementById('category-disposal-rate-display');
        const valueDisplay = document.getElementById('category-disposal-value-display');

        const catData = getCategoryData();
        const costRaw = document.getElementById('cost')?.value?.replace(/,/g, '');
        const yearVal = document.getElementById('year_bought')?.value;

        // Update read-only category preset cards
        if (lifeDisplay) {
            lifeDisplay.textContent = catData.life ? catData.life + ' years' : '-';
        }
        if (rateDisplay) {
            rateDisplay.textContent = catData.disposalPercent !== null ? catData.disposalPercent.toFixed(2) + '%' : '-';
        }

        const cost = (costRaw && !isNaN(parseFloat(costRaw)) && parseFloat(costRaw) > 0) ? parseFloat(costRaw) : null;

        if (valueDisplay) {
            if (cost !== null && catData.disposalPercent !== null) {
                const dispVal = Math.round((cost * (catData.disposalPercent / 100)) * 100) / 100;
                valueDisplay.textContent = '₱' + dispVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                valueDisplay.textContent = '-';
            }
        }

        if (!previewText) return;

        // If either Category or Cost is missing
        if (!catData.life || cost === null) {
            previewText.textContent = 'Select a category and enter a cost to calculate';
            return;
        }

        const usefulLife = catData.life;
        const disposalFloor = Math.round((cost * (catData.disposalPercent / 100)) * 100) / 100;

        if (!yearVal || isNaN(parseInt(yearVal, 10)) || parseInt(yearVal, 10) <= 0) {
            previewText.textContent = 'Enter year bought to calculate elapsed depreciation';
            return;
        }

        const acquiredDate = new Date(parseInt(yearVal, 10), 0, 1);
        const now = new Date();

        if (acquiredDate > now) {
            previewText.textContent = '₱' + cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (no depreciation yet)';
            return;
        }

        const depreciable = Math.max(0, cost - disposalFloor);
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
            current = disposalFloor;
        } else {
            accumulated = Math.min(depreciable, monthlyRate * monthsElapsed);
            current = Math.max(disposalFloor, cost - accumulated);
        }

        previewText.textContent = '₱' + current.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const costInput = document.getElementById('cost');
        if (costInput && costInput.value) {
            costInput.value = formatWithCommas(costInput.value);
        }
        const yearInput = document.getElementById('year_bought');
        if (yearInput) {
            yearInput.addEventListener('input', updateDepreciationPreview);
            yearInput.addEventListener('change', updateDepreciationPreview);
        }
        updateDepreciationPreview();
    });
</script>
@endif

<script>
    function handleDepartmentChange(select) {
        if (!select) return;
        const option = select.options[select.selectedIndex];
        const confirmed = option ? option.dataset.confirmed : '1';
        const notice = document.getElementById('dept-unconfirmed-notice');
        if (notice) {
            if (confirmed === '0') {
                notice.classList.remove('hidden');
            } else {
                notice.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const deptSelect = document.getElementById('department_id');
        if (deptSelect) {
            handleDepartmentChange(deptSelect);
        }
        const sectionSelect = document.getElementById('section_id');
        const sectionUnconfirmedNotice = document.getElementById('section-unconfirmed-notice');
        const sectionEmptyNotice = document.getElementById('section-empty-notice');
        const assetNoInput = document.getElementById('asset_no');
        const assetNoHelp = document.getElementById('asset_no-help');
        const useBtn = document.getElementById('use-suggested-asset-no-btn');
        const isCreateMode = {{ ! isset($asset) ? 'true' : 'false' }};

        if (!deptSelect || !sectionSelect) return;

        const masterSections = Array.from(sectionSelect.options)
            .filter(function (opt) { return opt.value !== ''; })
            .map(function (opt) {
                return {
                    value: opt.value,
                    text: opt.textContent,
                    departmentId: opt.dataset.departmentId || '',
                    code: opt.dataset.code || '',
                    confirmed: opt.dataset.confirmed || '1',
                    initiallySelected: opt.selected
                };
            });

        function syncSectionNotice() {
            if (!sectionUnconfirmedNotice) return;
            const opt = sectionSelect.options[sectionSelect.selectedIndex];
            const confirmed = opt && opt.value ? (opt.dataset.confirmed || '1') : '1';
            if (confirmed === '0') {
                sectionUnconfirmedNotice.classList.remove('hidden');
            } else {
                sectionUnconfirmedNotice.classList.add('hidden');
            }
        }

        function rebuildSections(preserveCurrent) {
            const deptId = deptSelect.value ? String(deptSelect.value) : '';
            const prevValue = preserveCurrent
                ? (sectionSelect.value || (masterSections.find(function (s) { return s.initiallySelected; })?.value || ''))
                : '';

            while (sectionSelect.options.length > 0) {
                sectionSelect.remove(0);
            }

            const placeholderOpt = document.createElement('option');
            placeholderOpt.value = '';
            placeholderOpt.textContent = deptId ? '-- Select Section --' : '-- Select a Department first --';
            sectionSelect.appendChild(placeholderOpt);

            const matching = deptId
                ? masterSections.filter(function (s) { return String(s.departmentId) === deptId; })
                : [];

            matching.forEach(function (sec) {
                const option = document.createElement('option');
                option.value = sec.value;
                option.textContent = sec.text;
                option.dataset.departmentId = sec.departmentId;
                option.dataset.code = sec.code;
                option.dataset.confirmed = sec.confirmed;
                if (prevValue && String(prevValue) === String(sec.value)) {
                    option.selected = true;
                }
                sectionSelect.appendChild(option);
            });

            if (sectionEmptyNotice) {
                if (deptId && matching.length === 0) {
                    sectionEmptyNotice.classList.remove('hidden');
                } else {
                    sectionEmptyNotice.classList.add('hidden');
                }
            }

            syncSectionNotice();
            if (isCreateMode) {
                refreshAssetNoPreview();
            }
        }

        function refreshAssetNoPreview() {
            if (!isCreateMode || !assetNoInput) return;

            const deptId = deptSelect.value;
            const deptOption = deptSelect.options[deptSelect.selectedIndex];
            const deptConfirmed = deptOption ? deptOption.dataset.confirmed : '0';
            const secId = sectionSelect.value || '';
            const secOption = sectionSelect.options[sectionSelect.selectedIndex];
            const secConfirmed = secOption && secId ? (secOption.dataset.confirmed || '1') : '1';

            if (!deptId || deptConfirmed !== '1' || secConfirmed !== '1') {
                const fallback = useBtn ? (useBtn.dataset.fallbackNo || 'BBH-000001') : 'BBH-000001';
                assetNoInput.placeholder = 'Auto-generate (e.g. ' + fallback + ')';
                if (assetNoHelp) {
                    assetNoHelp.textContent = 'Leave blank to assign ' + fallback + ' automatically, or enter an existing hospital paper tag code (e.g. BBHIND-S300002315)';
                }
                if (useBtn) {
                    useBtn.dataset.previewNo = '';
                    useBtn.textContent = 'Use ' + fallback;
                }
                return;
            }

            fetch('/departments/' + encodeURIComponent(deptId) + '/preview-next-number?section_id=' + encodeURIComponent(secId), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.next_number) return;
                assetNoInput.placeholder = 'Auto-generate (e.g. ' + data.next_number + ')';
                if (assetNoHelp) {
                    assetNoHelp.textContent = 'Leave blank to assign ' + data.next_number + ' automatically, or enter an existing hospital paper tag code (e.g. BBHIND-S300002315)';
                }
                if (useBtn) {
                    useBtn.dataset.previewNo = data.next_number;
                    useBtn.textContent = 'Use ' + data.next_number;
                }
            })
            .catch(function () {});
        }

        deptSelect.addEventListener('change', function () {
            rebuildSections(false);
        });

        sectionSelect.addEventListener('change', function () {
            syncSectionNotice();
            if (isCreateMode) {
                refreshAssetNoPreview();
            }
        });

        rebuildSections(true);

        const vendorSearchInput = document.getElementById('vendor_search_input');
        const vendorSelect = document.getElementById('vendor_id');
        const vendorResultsPopover = document.getElementById('vendor_results_popover');
        const vendorClearBtn = document.getElementById('vendor_clear_btn');
        const vendorToggleBtn = document.getElementById('vendor_dropdown_toggle_btn');
        const vendorComboContainer = document.getElementById('vendor-combobox-container');
        const vendorInlineFeedback = document.getElementById('vendor_inline_feedback');
        const isAdminUser = {{ auth()->user()?->isAdmin() ? 'true' : 'false' }};

        if (vendorSearchInput && vendorSelect && vendorResultsPopover) {
            let vendorItems = Array.from(vendorSelect.options)
                .filter(function (opt) { return opt.value !== ''; })
                .map(function (opt) {
                    return {
                        id: String(opt.value),
                        name: opt.dataset.name || opt.text.replace(/\s*\(Donation\)|\s*\[Inactive\]/g, '').trim(),
                        label: opt.text.trim(),
                        isDonation: opt.dataset.donation === '1',
                        active: opt.dataset.active !== '0',
                        searchName: (opt.dataset.name || opt.text).toLowerCase(),
                    };
                });

            function setVendorSelection(item) {
                if (vendorInlineFeedback) {
                    vendorInlineFeedback.classList.add('hidden');
                    vendorInlineFeedback.textContent = '';
                }
                if (!item) {
                    vendorSelect.value = '';
                    vendorSearchInput.value = '';
                    if (vendorClearBtn) vendorClearBtn.classList.add('hidden');
                } else {
                    vendorSelect.value = item.id;
                    vendorSearchInput.value = item.label;
                    if (vendorClearBtn) vendorClearBtn.classList.remove('hidden');
                }
                vendorResultsPopover.classList.add('hidden');
                vendorSearchInput.setAttribute('aria-expanded', 'false');
            }

            function renderVendorPopover(queryText) {
                const rawQuery = (queryText || '').trim();
                const query = rawQuery.toLowerCase();
                vendorResultsPopover.innerHTML = '';

                // If user has not typed a filter or query matches current selected label, show all
                const selectedItem = vendorItems.find(function (v) { return v.id === String(vendorSelect.value); });
                const effectiveQuery = (selectedItem && rawQuery === selectedItem.label) ? '' : query;

                const matches = vendorItems.filter(function (item) {
                    return effectiveQuery === '' || item.searchName.indexOf(effectiveQuery) !== -1;
                });

                // Option to clear / leave blank at top when no filter typed
                if (effectiveQuery === '') {
                    const noneBtn = document.createElement('button');
                    noneBtn.type = 'button';
                    noneBtn.className = 'w-full text-left px-3.5 py-2 text-xs text-slate-500 hover:bg-slate-50 transition-colors cursor-pointer italic';
                    noneBtn.textContent = '-- No Vendor / Leave Blank --';
                    noneBtn.addEventListener('click', function () {
                        setVendorSelection(null);
                    });
                    vendorResultsPopover.appendChild(noneBtn);
                }

                if (matches.length === 0) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'p-3.5 text-xs text-slate-600 space-y-2';

                    const msg = document.createElement('p');
                    msg.textContent = 'No matching vendor found for "' + rawQuery + '".';
                    emptyDiv.appendChild(msg);

                    if (isAdminUser && rawQuery.length > 0) {
                        const addBtn = document.createElement('button');
                        addBtn.type = 'button';
                        addBtn.className = 'w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs shadow-2xs transition-colors cursor-pointer';
                        addBtn.textContent = '+ Add "' + rawQuery.toUpperCase() + '" as a new vendor';
                        addBtn.addEventListener('click', function () {
                            createNewVendorInline(rawQuery, addBtn);
                        });
                        emptyDiv.appendChild(addBtn);
                    }

                    vendorResultsPopover.appendChild(emptyDiv);
                } else {
                    matches.forEach(function (item) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        const isSelected = String(vendorSelect.value) === item.id;
                        btn.className = 'w-full text-left px-3.5 py-2.5 hover:bg-slate-50 focus:bg-slate-50 transition-colors cursor-pointer flex items-center justify-between gap-2 ' + (isSelected ? 'bg-sky-50/70 font-semibold' : '');

                        const nameSpan = document.createElement('span');
                        nameSpan.className = 'text-xs font-medium text-slate-900 truncate';
                        nameSpan.textContent = item.name;
                        btn.appendChild(nameSpan);

                        const badgesDiv = document.createElement('div');
                        badgesDiv.className = 'flex items-center gap-1.5 shrink-0';

                        if (item.isDonation) {
                            const donBadge = document.createElement('span');
                            donBadge.className = 'px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-semibold';
                            donBadge.textContent = 'Donation';
                            badgesDiv.appendChild(donBadge);
                        }
                        if (!item.active) {
                            const inactBadge = document.createElement('span');
                            inactBadge.className = 'px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium';
                            inactBadge.textContent = 'Inactive';
                            badgesDiv.appendChild(inactBadge);
                        }
                        if (badgesDiv.childNodes.length > 0) {
                            btn.appendChild(badgesDiv);
                        }

                        btn.addEventListener('click', function () {
                            setVendorSelection(item);
                        });

                        vendorResultsPopover.appendChild(btn);
                    });
                }

                vendorResultsPopover.classList.remove('hidden');
                vendorSearchInput.setAttribute('aria-expanded', 'true');
            }

            function createNewVendorInline(rawName, btnEl) {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.value
                    || '';
                if (btnEl) btnEl.disabled = true;

                fetch('{{ route('settings.vendors.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ name: rawName.trim().toUpperCase() }),
                })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    const v = result.data && result.data.vendor ? result.data.vendor : null;
                    if (!v) {
                        if (vendorInlineFeedback) {
                            vendorInlineFeedback.textContent = (result.data && result.data.message) ? result.data.message : 'Could not add vendor.';
                            vendorInlineFeedback.className = 'text-xs font-medium mt-1 text-red-600';
                        }
                        return;
                    }

                    const newItem = {
                        id: String(v.id),
                        name: v.name,
                        label: v.name + (v.is_donation ? ' (Donation)' : ''),
                        isDonation: !!v.is_donation,
                        active: true,
                        searchName: v.name.toLowerCase(),
                    };

                    if (!vendorItems.some(function (existing) { return existing.id === newItem.id; })) {
                        vendorItems.push(newItem);
                        const opt = document.createElement('option');
                        opt.value = newItem.id;
                        opt.text = newItem.label;
                        opt.dataset.name = newItem.name;
                        opt.dataset.donation = newItem.isDonation ? '1' : '0';
                        opt.dataset.active = '1';
                        vendorSelect.appendChild(opt);
                    }

                    setVendorSelection(newItem);
                    if (vendorInlineFeedback) {
                        vendorInlineFeedback.textContent = 'Added vendor "' + newItem.name + '" and selected it.';
                        vendorInlineFeedback.className = 'text-xs font-medium mt-1 text-emerald-700';
                    }
                })
                .catch(function () {
                    if (vendorInlineFeedback) {
                        vendorInlineFeedback.textContent = 'Could not add vendor. Please try again.';
                        vendorInlineFeedback.className = 'text-xs font-medium mt-1 text-red-600';
                    }
                })
                .finally(function () {
                    if (btnEl) btnEl.disabled = false;
                });
            }

            vendorSearchInput.addEventListener('focus', function () {
                renderVendorPopover(vendorSearchInput.value);
            });

            vendorSearchInput.addEventListener('click', function () {
                renderVendorPopover(vendorSearchInput.value);
            });

            vendorSearchInput.addEventListener('input', function () {
                const q = vendorSearchInput.value.trim();
                if (q === '') {
                    vendorSelect.value = '';
                    if (vendorClearBtn) vendorClearBtn.classList.add('hidden');
                }
                renderVendorPopover(vendorSearchInput.value);
            });

            if (vendorToggleBtn) {
                vendorToggleBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (vendorResultsPopover.classList.contains('hidden')) {
                        vendorSearchInput.focus();
                        renderVendorPopover('');
                    } else {
                        vendorResultsPopover.classList.add('hidden');
                        vendorSearchInput.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            if (vendorClearBtn) {
                vendorClearBtn.addEventListener('click', function () {
                    setVendorSelection(null);
                    vendorSearchInput.focus();
                });
            }

            document.addEventListener('click', function (evt) {
                if (vendorComboContainer && !vendorComboContainer.contains(evt.target)) {
                    vendorResultsPopover.classList.add('hidden');
                    vendorSearchInput.setAttribute('aria-expanded', 'false');
                    // Restore selected label if user typed partial text without picking
                    const currentId = String(vendorSelect.value || '');
                    const currentItem = vendorItems.find(function (v) { return v.id === currentId; });
                    vendorSearchInput.value = currentItem ? currentItem.label : '';
                    if (vendorClearBtn) {
                        vendorClearBtn.classList.toggle('hidden', !currentItem);
                    }
                }
            });
        }

        initMasterlistPicker();
    });

    let pendingMasterlistTemplate = null;
    let masterlistDebounceTimer = null;

    function initMasterlistPicker() {
        const searchInput = document.getElementById('masterlist_search');
        const resultsBox = document.getElementById('masterlist_results');
        if (!searchInput || !resultsBox) return;

        function fetchTemplates(query) {
            fetch('/api/asset-templates/search?q=' + encodeURIComponent(query || ''), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) { return res.ok ? res.json() : []; })
            .then(function (items) {
                renderMasterlistResults(Array.isArray(items) ? items : []);
            })
            .catch(function () {});
        }

        function renderMasterlistResults(items) {
            resultsBox.innerHTML = '';
            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'px-3.5 py-2.5 text-xs text-slate-500';
                empty.textContent = 'No matching equipment templates found. You can enter details manually below.';
                resultsBox.appendChild(empty);
                resultsBox.classList.remove('hidden');
                return;
            }

            items.forEach(function (item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3.5 py-2.5 hover:bg-slate-50 focus:bg-slate-50 transition-colors cursor-pointer flex items-center justify-between gap-3';

                const left = document.createElement('div');
                left.className = 'min-w-0';

                const title = document.createElement('div');
                title.className = 'text-xs font-semibold text-slate-900 truncate';
                title.textContent = item.name || '';
                left.appendChild(title);

                const subParts = [item.brand, item.model].filter(Boolean);
                if (subParts.length) {
                    const sub = document.createElement('div');
                    sub.className = 'text-[11px] text-slate-500 truncate';
                    sub.textContent = subParts.join(' - ');
                    left.appendChild(sub);
                }

                btn.appendChild(left);

                if (item.category_name) {
                    const badge = document.createElement('span');
                    badge.className = 'shrink-0 px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-medium';
                    badge.textContent = item.category_name;
                    btn.appendChild(badge);
                }

                btn.addEventListener('click', function () {
                    resultsBox.classList.add('hidden');
                    handleMasterlistTemplatePick(item);
                });

                resultsBox.appendChild(btn);
            });

            resultsBox.classList.remove('hidden');
        }

        searchInput.addEventListener('focus', function () {
            fetchTemplates(searchInput.value.trim());
        });

        searchInput.addEventListener('input', function () {
            clearTimeout(masterlistDebounceTimer);
            masterlistDebounceTimer = setTimeout(function () {
                fetchTemplates(searchInput.value.trim());
            }, 120);
        });

        document.addEventListener('click', function (evt) {
            const box = document.getElementById('masterlist-picker-box');
            if (box && !box.contains(evt.target)) {
                resultsBox.classList.add('hidden');
            }
        });
    }

    function hasExistingMasterlistTargetValues() {
        const assetName = document.getElementById('asset_name');
        const brand = document.getElementById('brand');
        const model = document.getElementById('model');
        const category = document.getElementById('category_id');
        const notes = document.getElementById('notes');

        return [
            assetName ? assetName.value.trim() : '',
            brand ? brand.value.trim() : '',
            model ? model.value.trim() : '',
            category ? String(category.value || '').trim() : '',
            notes ? notes.value.trim() : '',
        ].some(function (val) { return val !== ''; });
    }

    function handleMasterlistTemplatePick(tpl) {
        if (!tpl) return;
        if (hasExistingMasterlistTargetValues()) {
            pendingMasterlistTemplate = tpl;
            const confirmBox = document.getElementById('masterlist_replace_confirm');
            const labelEl = document.getElementById('masterlist_pending_label');
            if (labelEl) {
                labelEl.textContent = tpl.name || '';
            }
            if (confirmBox) {
                confirmBox.classList.remove('hidden');
            }
            return;
        }

        applyMasterlistTemplate(tpl);
    }

    function confirmPendingTemplateReplace() {
        if (pendingMasterlistTemplate) {
            applyMasterlistTemplate(pendingMasterlistTemplate);
        }
        cancelPendingTemplateReplace();
    }

    function cancelPendingTemplateReplace() {
        pendingMasterlistTemplate = null;
        const confirmBox = document.getElementById('masterlist_replace_confirm');
        if (confirmBox) {
            confirmBox.classList.add('hidden');
        }
    }

    function applyMasterlistTemplate(tpl) {
        const hiddenId = document.getElementById('asset_template_id');
        const searchInput = document.getElementById('masterlist_search');
        const clearBtn = document.getElementById('masterlist_clear_btn');
        const badge = document.getElementById('masterlist_selected_badge');

        const assetName = document.getElementById('asset_name');
        const brand = document.getElementById('brand');
        const model = document.getElementById('model');
        const category = document.getElementById('category_id');
        const notes = document.getElementById('notes');

        if (hiddenId) hiddenId.value = tpl.id ? String(tpl.id) : '';
        if (assetName) assetName.value = tpl.name || '';
        if (brand) brand.value = tpl.brand || '';
        if (model) model.value = tpl.model || '';
        if (notes) notes.value = tpl.default_remarks || '';
        if (category) {
            category.value = tpl.category_id ? String(tpl.category_id) : '';
            if (typeof handleCategoryChange === 'function') {
                handleCategoryChange(category);
            } else {
                category.dispatchEvent(new Event('change'));
            }
        }

        const displayParts = [tpl.name, [tpl.brand, tpl.model].filter(Boolean).join(' ')].filter(Boolean);
        if (searchInput) searchInput.value = displayParts.join(' - ');
        if (clearBtn) clearBtn.classList.remove('hidden');
        if (badge) {
            badge.textContent = 'Selected masterlist item: ' + (tpl.name || '') + (tpl.category_name ? ' (' + tpl.category_name + ')' : '');
            badge.classList.remove('hidden');
        }
    }

    function clearSelectedMasterlist() {
        const hiddenId = document.getElementById('asset_template_id');
        const searchInput = document.getElementById('masterlist_search');
        const clearBtn = document.getElementById('masterlist_clear_btn');
        const badge = document.getElementById('masterlist_selected_badge');
        cancelPendingTemplateReplace();
        if (hiddenId) hiddenId.value = '';
        if (searchInput) searchInput.value = '';
        if (clearBtn) clearBtn.classList.add('hidden');
        if (badge) {
            badge.textContent = '';
            badge.classList.add('hidden');
        }
    }

    function toggleInlineMasterlistForm(forceState) {
        const panel = document.getElementById('inline-masterlist-create-panel');
        if (!panel) return;
        const show = typeof forceState === 'boolean' ? forceState : panel.classList.contains('hidden');
        if (show) {
            const nameEl = document.getElementById('inline_tpl_name');
            const brandEl = document.getElementById('inline_tpl_brand');
            const modelEl = document.getElementById('inline_tpl_model');
            const catEl = document.getElementById('inline_tpl_category_id');
            const remarksEl = document.getElementById('inline_tpl_remarks');
            if (nameEl && !nameEl.value) nameEl.value = document.getElementById('asset_name')?.value || '';
            if (brandEl && !brandEl.value) brandEl.value = document.getElementById('brand')?.value || '';
            if (modelEl && !modelEl.value) modelEl.value = document.getElementById('model')?.value || '';
            if (catEl && !catEl.value) catEl.value = document.getElementById('category_id')?.value || '';
            if (remarksEl && !remarksEl.value) remarksEl.value = document.getElementById('notes')?.value || '';
            panel.classList.remove('hidden');
        } else {
            panel.classList.add('hidden');
        }
    }

    function submitInlineMasterlistTemplate() {
        const errorBox = document.getElementById('inline_tpl_error');
        const btn = document.getElementById('inline_tpl_save_btn');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value
            || '';

        const payload = {
            name: (document.getElementById('inline_tpl_name')?.value || '').trim(),
            brand: (document.getElementById('inline_tpl_brand')?.value || '').trim() || null,
            model: (document.getElementById('inline_tpl_model')?.value || '').trim() || null,
            category_id: document.getElementById('inline_tpl_category_id')?.value || null,
            default_remarks: (document.getElementById('inline_tpl_remarks')?.value || '').trim() || null,
        };

        if (errorBox) errorBox.classList.add('hidden');
        if (btn) btn.disabled = true;

        fetch('/settings/asset-templates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || !result.data.template) {
                const msg = (result.data && result.data.message) ? result.data.message : 'Unable to save template.';
                if (errorBox) {
                    errorBox.textContent = msg;
                    errorBox.classList.remove('hidden');
                }
                return;
            }
            applyMasterlistTemplate(result.data.template);
            toggleInlineMasterlistForm(false);
        })
        .catch(function () {
            if (errorBox) {
                errorBox.textContent = 'Unable to save template. Please check your connection and try again.';
                errorBox.classList.remove('hidden');
            }
        })
        .finally(function () {
            if (btn) btn.disabled = false;
        });
    }
</script>
