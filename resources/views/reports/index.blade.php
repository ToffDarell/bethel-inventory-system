@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-page-header
            title="Reports & Excel Import / Export"
            subtitle="Download filtered equipment registers as Excel spreadsheets, or import property data into the hospital database"
        />

        <!-- 4-Step Import Stepper -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 font-semibold flex items-center justify-center shrink-0">1</span>
                    <div>
                        <p class="font-medium text-slate-900">Download Template</p>
                        <p class="text-[11px] text-slate-500">Get standard spreadsheet</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-sky-50/70 border border-sky-200">
                    <span class="w-6 h-6 rounded-full bg-primary text-white font-semibold flex items-center justify-center shrink-0">2</span>
                    <div>
                        <p class="font-semibold text-slate-900">Upload File</p>
                        <p class="text-[11px] text-slate-600">Select .xlsx or .csv</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white border border-slate-100 opacity-60">
                    <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 font-semibold flex items-center justify-center shrink-0">3</span>
                    <div>
                        <p class="font-medium text-slate-700">Review Preview</p>
                        <p class="text-[11px] text-slate-400">Verify rows and duplicates</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white border border-slate-100 opacity-60">
                    <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 font-semibold flex items-center justify-center shrink-0">4</span>
                    <div>
                        <p class="font-medium text-slate-700">Confirm &amp; Import</p>
                        <p class="text-[11px] text-slate-400">Save to inventory records</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-950" role="alert" tabindex="-1">
                <p class="text-sm font-semibold text-red-900 mb-1">Please review the import form</p>
                <ul class="list-disc list-inside text-xs text-red-800 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Export Assets to Excel -->
            <x-card title="Export Assets to Excel" subtitle="Download current hospital equipment records as an Excel (.xlsx) spreadsheet">
                <p class="text-sm text-slate-600 mb-5">
                    Generate an inventory spreadsheet of all registered hospital property ({{ $totalAssets }} total {{ Str::plural('asset', $totalAssets) }}). Filter by department or status if needed.
                </p>

                <form method="GET" action="{{ route('reports.export') }}" class="space-y-4">
                    <div>
                        <label for="export_department" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Department Filter
                        </label>
                        <select id="export_department"
                                name="department"
                                class="w-full min-h-[44px] px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                            <option value="">All departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="export_status" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Status Filter
                        </label>
                        <select id="export_status"
                                name="status"
                                class="w-full min-h-[44px] px-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-medium text-sm shadow-2xs transition-colors cursor-pointer">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Export to Excel (.xlsx)</span>
                        </button>
                    </div>
                </form>
            </x-card>

            <!-- Import Assets from Excel -->
            <x-card title="Import Assets from Excel" subtitle="Bulk-upload equipment records from a spreadsheet with pre-save validation">
                <p class="text-sm text-slate-600 mb-4">
                    Upload an Excel (<code>.xlsx</code>, <code>.xls</code>) or CSV (<code>.csv</code>) file. You will preview all rows and duplicate checks before anything is saved.
                </p>

                <!-- Step 1 Template Download Box -->
                <div class="mb-5 p-3.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-slate-900">Need the template format?</p>
                        <p class="text-[11px] text-slate-500">Download the standard column layout</p>
                    </div>
                    <a href="{{ route('reports.template') }}"
                       class="min-h-[38px] inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors shrink-0">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Download Template</span>
                    </a>
                </div>

                <form method="POST"
                      action="{{ route('reports.import.preview') }}"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label for="import_file" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Spreadsheet File <span class="text-brand-red font-semibold">*</span>
                        </label>
                        <input id="import_file"
                               name="file"
                               type="file"
                               required
                               accept=".xlsx,.xls,.csv"
                               class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 focus:outline-none focus:border-primary transition-colors">
                        <p class="text-xs text-slate-500 mt-1">
                            <code>date_acquired</code> and <code>serial_no</code> are optional. Blank <code>asset_no</code> rows auto-generate sequential codes.
                        </p>
                    </div>

                    <div class="pt-2">
                        <x-button variant="primary" type="submit" class="w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span>Preview Import Rows</span>
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
@endsection
