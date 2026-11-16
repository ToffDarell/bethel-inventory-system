@extends('layouts.app')

@section('content')
    <style>
        .rpt-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
            line-height: 1.3;
            color: #0f172a;
        }
        .rpt-table th {
            background-color: #dbeafe;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            padding: 4px 6px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: left;
            color: #0f172a;
            font-size: 10.5px;
            letter-spacing: 0.02em;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .rpt-table td {
            padding: 2.5px 6px;
            vertical-align: top;
            overflow-wrap: break-word;
        }
        .rpt-subgroup-header td {
            padding-top: 10px;
            padding-bottom: 3px;
            font-weight: 700;
            font-style: italic;
            color: #065f46;
            border-bottom: 1px solid #334155;
            font-size: 11px;
        }
        .rpt-subtotal-row td {
            padding-top: 3px;
            padding-bottom: 6px;
            border-top: 1px solid #334155;
            font-weight: 700;
            color: #065f46;
            font-size: 11px;
        }
        .rpt-grouptotal-row td {
            padding: 5px 6px;
            background-color: #f1f5f9;
            border-top: 1.5px solid #334155;
            border-bottom: 1.5px solid #334155;
            font-weight: 800;
            color: #0f172a;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .rpt-num {
            text-align: right !important;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 10mm 12mm 10mm;
            }
            body {
                background: #ffffff !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            header,
            nav,
            footer,
            .no-print {
                display: none !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .print-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .rpt-primary-block {
                margin-bottom: 16px;
            }
        }
    </style>

    <div class="space-y-5">
        <!-- Interactive Print Preview Toolbar (Hidden on Print) -->
        <div class="no-print bg-white border border-slate-200 rounded-xl p-4 shadow-2xs space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('reports.index') }}"
                       class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Back to Reports</span>
                    </a>

                    <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1 rounded-lg border border-slate-200">
                        <a href="{{ route('reports.print', array_merge(request()->except('type'), ['type' => 'category'])) }}"
                           class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors {{ $reportType === 'category' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Asset by Category
                        </a>
                        <a href="{{ route('reports.print', array_merge(request()->except('type'), ['type' => 'date_acquired'])) }}"
                           class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors {{ $reportType === 'date_acquired' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Asset by Date Acquired
                        </a>
                        <a href="{{ route('reports.print', array_merge(request()->except('type'), ['type' => 'department'])) }}"
                           class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors {{ $reportType === 'department' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Asset by Department
                        </a>
                        <a href="{{ route('reports.print', array_merge(request()->except('type'), ['type' => 'vendor'])) }}"
                           class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors {{ $reportType === 'vendor' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                            Asset by Vendor
                        </a>
                        @if ($isAdmin)
                            <a href="{{ route('reports.depreciation') }}"
                               class="px-3 py-1.5 rounded-md text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                                Depreciation Summary
                            </a>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button type="button"
                            onclick="window.print()"
                            class="min-h-[40px] inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-xs transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                        </svg>
                        <span>Print Report</span>
                    </button>

                    <a href="{{ route('reports.export', ['department' => $filters['department'], 'status' => $filters['status'], 'format' => 'xlsx']) }}"
                       class="min-h-[40px] inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-colors">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Export Excel</span>
                    </a>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" action="{{ route('reports.print') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 pt-3 border-t border-slate-100">
                <input type="hidden" name="type" value="{{ $reportType }}">

                <div>
                    <label for="filter_category" class="block text-[11px] font-semibold text-slate-600 mb-1">Category</label>
                    <select id="filter_category" name="category_id" onchange="this.form.submit()" class="w-full min-h-[36px] px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) $filters['category_id'] === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                        <option value="uncategorized" {{ $filters['category_id'] === 'uncategorized' ? 'selected' : '' }}>Uncategorized</option>
                    </select>
                </div>

                <div>
                    <label for="filter_department" class="block text-[11px] font-semibold text-slate-600 mb-1">Department</label>
                    <select id="filter_department" name="department" onchange="this.form.submit()" class="w-full min-h-[36px] px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}" {{ $filters['department'] === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_vendor" class="block text-[11px] font-semibold text-slate-600 mb-1">Vendor / Supplier</label>
                    <select id="filter_vendor" name="vendor_id" onchange="this.form.submit()" class="w-full min-h-[36px] px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Vendors</option>
                        <option value="donations" {{ $filters['vendor_id'] === 'donations' ? 'selected' : '' }}>Donations Only</option>
                        <option value="none" {{ $filters['vendor_id'] === 'none' ? 'selected' : '' }}>No Vendor Recorded</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" {{ (string) $filters['vendor_id'] === (string) $v->id ? 'selected' : '' }}>
                                {{ $v->name }}{{ $v->is_donation ? ' (Donation)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_year" class="block text-[11px] font-semibold text-slate-600 mb-1">Year Acquired</label>
                    <select id="filter_year" name="year" onchange="this.form.submit()" class="w-full min-h-[36px] px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Years</option>
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}" {{ (string) $filters['year'] === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_status" class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                    <select id="filter_status" name="status" onchange="this.form.submit()" class="w-full min-h-[36px] px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st }}" {{ $filters['status'] === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <a href="{{ route('reports.print', ['type' => $reportType]) }}"
                       class="min-h-[36px] w-full inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-slate-600 transition-colors">
                        Reset Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Printable Report Document Sheet -->
        <div class="print-sheet bg-white border border-slate-300 rounded-xl shadow-sm p-6 sm:p-8 max-w-5xl mx-auto text-slate-900">
            <!-- Institutional Letterhead (Matches Screenshot 191) -->
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding-bottom: 10px;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    @if (! empty($logoDataUri))
                        <img src="{{ $logoDataUri }}" alt="Hospital Logo" style="height: 48px; width: auto; object-fit: contain;">
                    @endif
                    <div>
                        <div style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: #0f172a; line-height: 1.2;">
                            {{ $hospitalName }}
                        </div>
                        <div style="font-size: 11px; color: #334155; line-height: 1.35;">{{ $hospitalAddress }}</div>
                        <div style="font-size: 11px; color: #334155; line-height: 1.35;">Tel. No. : (088) 813-2229 / (088) 813-2708</div>
                    </div>
                </div>

                <div style="text-align: right; font-size: 11px; color: #334155; line-height: 1.4;">
                    <div>Total Assets : <strong style="color: #0f172a;">{{ number_format($grandCount) }}</strong></div>
                    <div>Date Printed : {{ now()->format('m/d/Y g:i:s A') }}</div>
                </div>
            </div>

            <!-- Report Title Link-Style Heading (Matches Screenshot 191 Blue Underlined Title) -->
            <div style="margin-top: 6px; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #1e3a8a; text-decoration: underline;">
                    {{ $reportTitle }}
                </span>
            </div>

            @forelse ($groups as $primaryGroup)
                <div class="rpt-primary-block" style="margin-bottom: 18px;">
                    <!-- Primary Group Header (Red Bold Text like Screenshot 191) -->
                    <div style="margin-bottom: 4px; font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #dc2626;">
                        {{ $primaryPrefix }} : {{ $primaryGroup['label'] }}
                    </div>

                    <table class="rpt-table">
                        <colgroup>
                            @if ($isAdmin)
                                <col style="width: 15%;">
                                <col style="width: 35%;">
                                <col style="width: 16%;">
                                <col style="width: 12%;">
                                <col style="width: 11%;">
                                <col style="width: 11%;">
                            @else
                                <col style="width: 16%;">
                                <col style="width: 40%;">
                                <col style="width: 18%;">
                                <col style="width: 14%;">
                                <col style="width: 12%;">
                            @endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th>BAR CODE</th>
                                <th>ASSET NAME</th>
                                <th>SERIAL NO</th>
                                <th>ACQUIRED</th>
                                @if ($isAdmin)
                                    <th class="rpt-num">PURCHASE</th>
                                    <th class="rpt-num">CURRENT</th>
                                @else
                                    <th class="rpt-num">STATUS</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($primaryGroup['subgroups'] as $subGroup)
                                <!-- Secondary Subgroup Header (Green Italic Text like Screenshot 191) -->
                                <tr class="rpt-subgroup-header">
                                    <td colspan="{{ $isAdmin ? 6 : 5 }}">
                                        {{ $secondaryPrefix }} : {{ $subGroup['label'] }}
                                    </td>
                                </tr>

                                @foreach ($subGroup['rows'] as $row)
                                    <tr>
                                        <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;">
                                            {{ $row['asset_no'] }}
                                        </td>
                                        <td>
                                            {{ $row['asset_name'] }}@if ($row['brand_model'] !== '') <span style="color: #475569;">({{ $row['brand_model'] }})</span>@endif
                                        </td>
                                        <td>
                                            {{ $row['serial_no'] }}
                                        </td>
                                        <td style="white-space: nowrap;">
                                            {{ $row['acquired'] }}
                                        </td>
                                        @if ($isAdmin)
                                            <td class="rpt-num">
                                                {{ number_format($row['purchase'], 2) }}
                                            </td>
                                            <td class="rpt-num">
                                                {{ number_format($row['current'], 2) }}
                                            </td>
                                        @else
                                            <td class="rpt-num">
                                                {{ $row['status'] }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach

                                <!-- Subgroup Subtotal Row -->
                                <tr class="rpt-subtotal-row">
                                    <td colspan="2">
                                        Asset Count : {{ number_format($subGroup['count']) }}
                                    </td>
                                    @if ($isAdmin)
                                        <td colspan="2">
                                            SUB-TOTAL
                                        </td>
                                        <td class="rpt-num">
                                            {{ number_format($subGroup['purchase_total'], 2) }}
                                        </td>
                                        <td class="rpt-num">
                                            {{ number_format($subGroup['current_total'], 2) }}
                                        </td>
                                    @else
                                        <td colspan="3" class="rpt-num">
                                            Sub-Group Count : {{ number_format($subGroup['count']) }} {{ \Illuminate\Support\Str::plural('item', $subGroup['count']) }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div style="padding: 32px; text-align: center; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 12px; color: #475569;">
                    No matching equipment found for this report filter.
                </div>
            @endforelse

            @if ($grandCount > 0)
                <table class="rpt-table" style="margin-top: 8px;">
                    <colgroup>
                        @if ($isAdmin)
                            <col style="width: 15%;">
                            <col style="width: 35%;">
                            <col style="width: 16%;">
                            <col style="width: 12%;">
                            <col style="width: 11%;">
                            <col style="width: 11%;">
                        @else
                            <col style="width: 16%;">
                            <col style="width: 40%;">
                            <col style="width: 18%;">
                            <col style="width: 14%;">
                            <col style="width: 12%;">
                        @endif
                    </colgroup>
                    <tbody>
                        <tr class="rpt-grouptotal-row">
                            <td colspan="2">
                                Total Asset Count : {{ number_format($grandCount) }}
                            </td>
                            @if ($isAdmin)
                                <td colspan="2">
                                    GRAND TOTAL
                                </td>
                                <td class="rpt-num">
                                    {{ number_format($grandPurchase, 2) }}
                                </td>
                                <td class="rpt-num">
                                    {{ number_format($grandCurrent, 2) }}
                                </td>
                            @else
                                <td colspan="3" class="rpt-num">
                                    {{ number_format($grandCount) }} TOTAL {{ strtoupper(\Illuminate\Support\Str::plural('ASSET', $grandCount)) }}
                                </td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            @endif

            <!-- Footer (Matches Screenshot 192 Page No & Date Printed) -->
            <div style="margin-top: 14px; padding-top: 6px; border-top: 1px solid #0f172a; display: flex; justify-content: space-between; align-items: flex-start; font-size: 10.5px; color: #0f172a; line-height: 1.4;">
                <div>
                    <div>Page No : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1</div>
                    <div>Date Printed : {{ now()->format('n/j/Y g:i:s A') }}</div>
                </div>
                <div style="text-align: right;">
                    <div>Prepared By : {{ auth()->user()?->name ?? auth()->user()?->username }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
