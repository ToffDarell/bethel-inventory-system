@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 text-base font-bold text-red-600 hover:underline mb-2">
                &larr; Back to Reports &amp; Import
            </a>
            <h2 class="text-3xl font-extrabold text-red-600">Import Preview</h2>
            <p class="text-base text-neutral-600 mt-1">
                Review the parsed spreadsheet rows below before saving them to the database.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('reports.index') }}"
               class="px-5 py-3 rounded-xl border-2 border-red-200 bg-white hover:bg-red-50 text-red-700 font-bold text-base transition">
                Cancel
            </a>

            @if ($valid_count > 0)
                <form method="POST" action="{{ route('reports.import.commit') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-base shadow-xs transition cursor-pointer">
                        <span>Confirm &amp; Import {{ $valid_count }} {{ Str::plural('Asset', $valid_count) }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="p-5 rounded-2xl border-2 border-red-600 bg-red-600 text-white">
            <p class="text-xs font-extrabold uppercase tracking-wider text-white">Ready to Import</p>
            <p class="text-3xl font-extrabold text-white tabular-nums mt-1">{{ $valid_count }}</p>
        </div>

        <div class="p-5 rounded-2xl border-2 border-red-300 bg-red-50">
            <p class="text-xs font-extrabold uppercase tracking-wider text-red-900">Duplicate Asset No. (Skipped)</p>
            <p class="text-3xl font-extrabold text-red-700 tabular-nums mt-1">{{ $duplicate_count }}</p>
        </div>

        <div class="p-5 rounded-2xl border-2 border-red-200 bg-white">
            <p class="text-xs font-extrabold uppercase tracking-wider text-neutral-600">Incomplete Rows (Skipped)</p>
            <p class="text-3xl font-extrabold text-red-600 tabular-nums mt-1">{{ $invalid_count }}</p>
        </div>
    </div>

    <div class="bg-white border-2 border-red-100 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-red-600 text-white text-xs font-extrabold uppercase tracking-wider">
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
                <tbody class="divide-y divide-red-100 text-base">
                    @forelse ($rows as $row)
                        <tr class="{{ $row['is_valid'] ? 'bg-white' : 'bg-red-50 text-neutral-600' }}">
                            <td class="p-3.5 font-mono text-sm">{{ $row['row_number'] }}</td>
                            <td class="p-3.5">
                                @if ($row['is_valid'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-600 text-white">
                                        {{ $row['status_label'] }}
                                    </span>
                                @elseif ($row['is_duplicate'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-white text-red-700 border border-red-300">
                                        {{ $row['status_label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-white text-red-700 border border-red-400">
                                        {{ $row['status_label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 font-mono font-bold">
                                @if ($row['asset_no'] !== '')
                                    {{ $row['asset_no'] }}
                                @else
                                    <span class="text-xs font-sans font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-200">
                                        Auto-generate
                                    </span>
                                @endif
                            </td>
                            <td class="p-3.5 font-bold text-neutral-900">{{ $row['asset_name'] ?: '—' }}</td>
                            <td class="p-3.5">{{ $row['model'] ?: '—' }}</td>
                            <td class="p-3.5 font-mono text-sm">{{ $row['serial_no'] ?: '—' }}</td>
                            <td class="p-3.5">{{ $row['date_acquired'] ?: '—' }}</td>
                            <td class="p-3.5">
                                @if ($row['department'] || $row['location'])
                                    <div class="font-semibold">{{ $row['location'] ?: '—' }}</div>
                                    @if ($row['department'])
                                        <div class="text-xs text-neutral-500">{{ $row['department'] }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3.5">{{ $row['status'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-neutral-600">
                                No data rows were found in the uploaded file.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
