@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <x-page-header
            title="Edit Asset"
            :subtitle="'Updating ' . $asset->asset_no . ': ' . $asset->asset_name"
            :backUrl="route('assets.show', $asset)"
            backLabel="Back to Asset"
        />

        <form id="edit-asset-form" method="POST" action="{{ route('assets.update', $asset) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="department_number_action" id="department_number_action" value="">

            @include('assets.partials.form-fields', ['asset' => $asset])

            <!-- Sticky Save & Cancel Bar -->
            <div class="sticky bottom-0 z-20 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-xl p-4 shadow-2xs flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    Changes update the register and property tag immediately
                </p>
                <div class="flex items-center gap-3">
                    <x-button variant="secondary" :href="route('assets.show', $asset)">
                        Cancel
                    </x-button>
                    <x-button variant="primary" type="submit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save Changes</span>
                    </x-button>
                </div>
            </div>
        </form>

        @include('assets.partials.department-change-modal', ['asset' => $asset])

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('edit-asset-form');
                const select = document.getElementById('department_id');
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

                    const sectionSelect = document.getElementById('section_id');
                    const sectionParam = sectionSelect && sectionSelect.value ? '?section_id=' + encodeURIComponent(sectionSelect.value) : '';

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
