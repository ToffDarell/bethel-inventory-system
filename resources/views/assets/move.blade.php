@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto">
        <x-page-header
            title="Move Asset"
            :subtitle="'Update the room or department of ' . $asset->asset_name . ' (' . $asset->asset_no . ') and log the transfer'"
            :backUrl="route('assets.show', $asset)"
            :backLabel="'Back to ' . $asset->asset_no"
        />

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-950" role="alert" tabindex="-1">
                <p class="text-sm font-semibold text-red-900 mb-1">Please check the form below</p>
                <ul class="list-disc list-inside text-xs text-red-800 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="move-asset-form" method="POST" action="{{ route('assets.move.store', $asset) }}" class="space-y-6">
            @csrf
            <input type="hidden" name="department_number_action" id="department_number_action" value="">

            <x-card title="Transfer Details" subtitle="Record where this equipment is being stationed">
                <div class="space-y-5">
                    <!-- Current Assignment Summary -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between gap-3 mb-2.5">
                            <span class="text-xs font-semibold text-slate-700">Equipment to Transfer</span>
                            <span class="font-mono text-xs font-medium text-slate-800 bg-white border border-slate-200 px-2.5 py-1 rounded">
                                {{ $asset->asset_no }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0 text-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $asset->asset_name }}</h3>
                                <p class="text-xs text-slate-600 mt-0.5">
                                    Current Location: <strong class="text-slate-800 font-medium">{{ $asset->location ?: 'Unassigned' }}</strong>
                                    @if ($asset->department)
                                        <span class="text-slate-300">&bull;</span> <span class="text-slate-500">{{ $asset->department }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <x-form-field
                        label="New Location"
                        name="to_location"
                        :required="true"
                        :autofocus="true"
                        placeholder="ND-Station 3 Room 39"
                        help="Enter the ward, station, or room where the asset is now located"
                    />

                    <div>
                        <label for="move_department_id" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Department
                        </label>
                        <select id="move_department_id"
                                name="department_id"
                                class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                            <option value="">-- Select Department --</option>
                            @foreach ($departments ?? [] as $d)
                                @php
                                    $dId = is_object($d) ? $d->id : null;
                                    $dName = is_object($d) ? $d->name : $d;
                                    $dCode = is_object($d) ? $d->code : '';
                                    $selected = (isset($asset) && $asset->department_id == $dId)
                                        || (isset($asset) && !empty($asset->department) && $asset->department === $dName);
                                @endphp
                                <option value="{{ $dId }}" {{ $selected ? 'selected' : '' }}>
                                    {{ $dName }} ({{ $dCode }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">If the department is changed, the asset number will automatically renumber and prompt tag reprint.</p>
                    </div>

                    @if (!app(\App\Services\AssetNumberService::class)->matchesPattern($asset->asset_no, $asset->departmentRelation) && auth()->user()?->isAdmin())
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-2">
                            <div class="font-semibold text-slate-800">Legacy / Paper Tag Number Detected</div>
                            <p class="text-slate-600">This asset currently uses a legacy paper tag number (<strong class="font-mono">{{ $asset->asset_no }}</strong>). If moving to a different department:</p>
                            <div class="flex items-center gap-4 pt-1">
                                <label class="inline-flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                    <input type="radio" name="paper_tag_number_action" value="keep" checked class="text-primary focus:ring-primary">
                                    Keep current number (default)
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                                    <input type="radio" name="paper_tag_number_action" value="assign_new" class="text-primary focus:ring-primary">
                                    Assign new department number
                                </label>
                            </div>
                        </div>
                    @endif

                    <x-form-field
                        label="Reason / Notes"
                        name="notes"
                        type="textarea"
                        placeholder="Transferred for patient admission in Room 39"
                        help="Optional reason or handover note recorded in the transfer audit log"
                    />
                </div>
            </x-card>

            <!-- Sticky Save & Cancel Bar -->
            <div class="sticky bottom-0 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-xl p-4 shadow-2xs flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    Transfer timestamp and your user account will be logged automatically
                </p>
                <div class="flex items-center gap-3">
                    <x-button variant="secondary" :href="route('assets.show', $asset)">
                        Cancel
                    </x-button>
                    <x-button variant="primary" type="submit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Confirm Move Asset</span>
                    </x-button>
                </div>
            </div>
        </form>

        @include('assets.partials.department-change-modal', ['asset' => $asset])

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('move-asset-form');
                const select = document.getElementById('move_department_id');
                const modal = document.getElementById('dept-change-confirm-modal');
                const oldNoSpan = document.getElementById('dept-modal-old-no');
                const newNoSpan = document.getElementById('dept-modal-new-no');
                const staffNote = document.getElementById('dept-staff-paper-warning');
                const btnCancel = document.getElementById('dept-modal-btn-cancel');
                const btnKeep = document.getElementById('dept-modal-btn-keep');
                const btnAssign = document.getElementById('dept-modal-btn-assign');
                const actionInput = document.getElementById('department_number_action');

                const originalDeptId = '{{ $asset->department_id ?? "" }}';
                const originalAssetNo = '{{ addslashes($asset->asset_no) }}';
                const isAdmin = {{ auth()->user()?->isAdmin() ? 'true' : 'false' }};
                const isPatterned = {{ app(\App\Services\AssetNumberService::class)->matchesPattern($asset->asset_no, $asset->departmentRelation) ? 'true' : 'false' }};
                const reassignEnabled = {{ \App\Models\Setting::get('reassign_number_on_dept_change', '1') === '1' ? 'true' : 'false' }};

                if (!form || !select || !modal) return;

                btnCancel.addEventListener('click', function () {
                    modal.classList.add('hidden');
                });

                btnKeep.addEventListener('click', function () {
                    actionInput.value = 'keep';
                    modal.classList.add('hidden');
                    form.submit();
                });

                btnAssign.addEventListener('click', function () {
                    actionInput.value = 'assign_new';
                    modal.classList.add('hidden');
                    form.submit();
                });

                form.addEventListener('submit', function (e) {
                    if (actionInput.value) {
                        return;
                    }

                    const currentDeptId = select.value ? String(select.value) : '';
                    const origDeptId = originalDeptId ? String(originalDeptId) : '';

                    const isDeptChanged = (currentDeptId !== origDeptId) && currentDeptId !== '';

                    if (!reassignEnabled || !isDeptChanged) {
                        return;
                    }

                    e.preventDefault();

                    const sectionParam = '?section_id=' + encodeURIComponent('{{ $asset->section_id ?? '' }}') + '&section=' + encodeURIComponent('{{ addslashes($asset->section ?? '') }}');

                    fetch('/departments/' + currentDeptId + '/preview-next-number' + sectionParam, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        const nextNo = data.next_number || '(Next available)';
                        if (oldNoSpan) oldNoSpan.textContent = originalAssetNo;
                        if (newNoSpan) newNoSpan.textContent = nextNo;

                        if (!isAdmin && !isPatterned) {
                            if (staffNote) staffNote.classList.remove('hidden');
                            if (btnAssign) btnAssign.classList.add('hidden');
                        } else {
                            if (staffNote) staffNote.classList.add('hidden');
                            if (btnAssign) btnAssign.classList.remove('hidden');
                        }

                        modal.classList.remove('hidden');
                    })
                    .catch(err => {
                        console.error('Failed to preview next department number:', err);
                        if (oldNoSpan) oldNoSpan.textContent = originalAssetNo;
                        if (newNoSpan) newNoSpan.textContent = '(Next available)';
                        modal.classList.remove('hidden');
                    });
                });
            });
        </script>
    </div>
@endsection
