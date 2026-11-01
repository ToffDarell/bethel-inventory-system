@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 text-base font-bold text-blue-700 hover:underline mb-2">
                &larr; Back to Reports &amp; Import
            </a>
            <h2 class="text-3xl font-bold text-slate-900">Import Preview</h2>
            <p class="text-base text-slate-600 mt-1">
                Review the parsed spreadsheet rows below before saving them to the database.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('reports.index') }}"
               class="px-5 py-3 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-base transition">
                Cancel
            </a>

            @if ($valid_count > 0)
                <form method="POST" action="{{ route('reports.import.commit') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-base shadow-sm transition cursor-pointer">
                        <span>Confirm &amp; Import {{ $valid_count }} {{ Str::plural('Asset', $valid_count) }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="p-5 rounded-xl border-2 border-emerald-300 bg-emerald-50">
            <p class="text-sm font-bold uppercase tracking-wide text-emerald-900">Ready to Import</p>
            <p class="text-3xl font-extrabold text-emerald-800 mt-1">{{ $valid_count }}</p>
        </div>

        <div class="p-5 rounded-xl border-2 border-amber-300 bg-amber-50">
            <p class="text-sm font-bold uppercase tracking-wide text-amber-900">Duplicate Asset No. (Skipped)</p>
            <p class="text-3xl font-extrabold text-amber-800 mt-1">{{ $duplicate_count }}</p>
        </div>

        <div class="p-5 rounded-xl border-2 border-red-300 bg-red-50">
            <p class="text-sm font-bold uppercase tracking-wide text-red-900">Incomplete Rows (Skipped)</p>
            <p class="text-3xl font-extrabold text-red-800 mt-1">{{ $invalid_count }}</p>
        </div>
    </div>

    <div class="bg-white border-2 border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b-2 border-slate-200 text-sm font-bold uppercase tracking-wide text-slate-800">
                        <th class="p-3.5">Row</th>
                        <th class="p-3.5">Import Action</th>
                        <th class="p-3.5">Asset No.</th>
                        <th class="p-3.5">Asset Name</th>
                        <th class="p-3.5">Model</th>
                        <th class="p-3.5">Serial No.</th>
                        <th class="p-3.5">Date Acquired</th>
                        <th class="p-3.5">Department / Location</th>
                        <th class="p-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-base">
                    @forelse ($rows as $row)
                        <tr class="{{ $row['is_valid'] ? 'bg-white' : 'bg-slate-50 text-slate-500' }}">
                            <td class="p-3.5 font-mono text-sm">{{ $row['row_number'] }}</td>
                            <td class="p-3.5">
                                @if ($row['is_valid'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        {{ $row['status_label'] }}
                                    </span>
                                @elseif ($row['is_duplicate'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        {{ $row['status_label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-900 border border-red-300">
                                        {{ $row['status_label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 font-mono font-bold">
                                @if ($row['asset_no'] !== '')
                                    {{ $row['asset_no'] }}
                                @else
                                    <span class="text-xs font-sans font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        Auto-generate
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 font-semibold">{{ $row['asset_name'] ?: '—' }}</td>
                            <td class="p-3.5">{{ $row['model'] ?: '—' }}</td>
                            <td class="p-3.5 font-mono text-sm">{{ $row['serial_no'] ?: '—' }}</td>
                            <td class="p-3.5">{{ $row['date_acquired'] ?: '—' }}</td>
                            <td class="p-3.5">
                                @if ($row['department'] || $row['location'])
                                    <div>{{ $row['location'] ?: '—' }}</div>
                                    @if ($row['department'])
                                        <div class="text-xs text-slate-500">{{ $row['department'] }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3.5">{{ $row['status'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-600">
                                No data rows were found in the uploaded file.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
