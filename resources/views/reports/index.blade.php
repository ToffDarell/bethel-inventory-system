@extends('layouts.app')

@section('content')
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-900">Reports &amp; Excel Import / Export</h2>
        <p class="text-base text-slate-600 mt-1">
            Download an Excel spreadsheet of hospital assets, or import your existing asset list from Excel.
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Export Assets to Excel -->
        <div class="p-6 bg-white border-2 border-slate-200 rounded-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Export Assets to Excel</h3>
                </div>

                <p class="text-base text-slate-600 mb-6">
                    Download the hospital's asset register ({{ $totalAssets }} total {{ Str::plural('asset', $totalAssets) }}) as an Excel (<code>.xlsx</code>) spreadsheet. You may optionally filter by department or status first.
                </p>

                <form method="GET" action="{{ route('reports.export') }}" class="space-y-4">
                    <div>
                        <label for="export_department" class="block text-base font-bold text-slate-900 mb-1.5">
                            Department Filter
                        </label>
                        <select id="export_department"
                                name="department"
                                class="w-full px-4 py-3 text-base border-2 border-slate-300 rounded-lg bg-white focus:outline-none focus:border-blue-700">
                            <option value="">All Departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="export_status" class="block text-base font-bold text-slate-900 mb-1.5">
                            Status Filter
                        </label>
                        <select id="export_status"
                                name="status"
                                class="w-full px-4 py-3 text-base border-2 border-slate-300 rounded-lg bg-white focus:outline-none focus:border-blue-700">
                            <option value="">All Statuses</option>
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Export to Excel (.xlsx)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import Assets from Excel -->
        <div class="p-6 bg-white border-2 border-slate-200 rounded-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Import Assets from Excel</h3>
                </div>

                <p class="text-base text-slate-600 mb-4">
                    Upload an Excel (<code>.xlsx</code>, <code>.xls</code>) or CSV (<code>.csv</code>) spreadsheet. You will see a preview table before anything is saved. Duplicate <code>asset_no</code> rows are skipped automatically.
                </p>

                <div class="mb-6 p-4 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-base font-bold text-slate-900">Need the column format?</p>
                        <p class="text-sm text-slate-600">Download our ready-to-fill sample Excel template.</p>
                    </div>
                    <a href="{{ route('reports.template') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-blue-700 bg-white hover:bg-blue-50 text-blue-700 font-bold text-sm shrink-0 transition">
                        <span>Download Sample Template</span>
                    </a>
                </div>

                <form method="POST"
                      action="{{ route('reports.import.preview') }}"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label for="import_file" class="block text-base font-bold text-slate-900 mb-2">
                            Select Excel or CSV File <span class="text-red-600">*</span>
                        </label>
                        <input id="import_file"
                               name="file"
                               type="file"
                               required
                               accept=".xlsx,.xls,.csv"
                               class="w-full px-4 py-3 text-base border-2 border-slate-300 rounded-lg bg-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-slate-500 mt-1.5">
                            <code>date_acquired</code> and <code>serial_no</code> are optional. Leave <code>asset_no</code> blank to auto-generate a number.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                            <span>Preview Import</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
