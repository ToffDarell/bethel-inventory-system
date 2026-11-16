<!-- Department Number Confirmation Modal -->
<div id="dept-change-confirm-modal"
     class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="dept-modal-title">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-slate-200">
        <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h3 id="dept-modal-title" class="text-base font-bold text-slate-900">
                    Changing the department
                </h3>
                <p id="dept-change-confirm-text" class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                    Changing the department will change this asset's number from <strong id="dept-modal-old-no" class="font-mono font-bold text-slate-900">{{ $asset->asset_no }}</strong> to <strong id="dept-modal-new-no" class="font-mono font-bold text-slate-900">...</strong>. The property tag must be reprinted.
                </p>
                <div id="dept-staff-paper-warning" class="hidden mt-2.5 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                    This asset uses a legacy paper tag number. Staff accounts keep current numbers; only an administrator can assign a new automated sequence.
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button"
                    id="dept-modal-btn-cancel"
                    class="w-full sm:w-auto px-3.5 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer">
                Cancel
            </button>
            <button type="button"
                    id="dept-modal-btn-keep"
                    class="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg shadow-2xs transition-colors cursor-pointer">
                Keep current number
            </button>
            <button type="button"
                    id="dept-modal-btn-assign"
                    class="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-slate-800 rounded-lg shadow-2xs transition-colors cursor-pointer">
                Assign new number
            </button>
        </div>
    </div>
</div>
