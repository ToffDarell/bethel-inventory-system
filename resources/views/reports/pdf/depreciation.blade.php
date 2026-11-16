<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Depreciation Report - {{ $hospitalName }}</title>
    <style>
        @page {
            size: {{ $paperSize }} landscape;
            margin: 18mm 14mm 16mm 14mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7.5pt;
            color: #0f172a;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2px solid #991b1b;
            padding-bottom: 6px;
        }

        .header-logo {
            width: 55px;
            vertical-align: middle;
        }

        .header-logo img {
            height: 44px;
            width: auto;
        }

        .header-content {
            vertical-align: middle;
            padding-left: 10px;
        }

        .hospital-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            line-height: 1.1;
        }

        .hospital-address {
            font-size: 7.5pt;
            color: #64748b;
            margin: 2px 0 3px 0;
        }

        .report-title-row {
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.3px;
        }

        .report-meta {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 1px;
        }

        .policy-box {
            font-size: 7.5pt;
            font-style: italic;
            color: #64748b;
            margin-bottom: 8px;
            padding: 4px 6px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.data-table thead {
            display: table-header-group;
        }

        table.data-table tr {
            page-break-inside: avoid;
        }

        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 5px 4px;
            text-align: center;
            border: 1px solid #0f172a;
            vertical-align: middle;
        }

        table.data-table td {
            font-size: 7.2pt;
            padding: 4px 4px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .row-even {
            background-color: #f8fafc;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #94a3b8; font-style: italic; }

        .totals-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 7.5pt;
            color: #0f172a;
            border-top: 1.5px solid #0f172a;
            border-bottom: 2.5px double #0f172a;
            padding: 5px 4px;
        }

        .note-row {
            margin-top: 6px;
            font-size: 7pt;
            font-style: italic;
            color: #64748b;
        }
    </style>
</head>
<body>

    {{-- Header Block --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <img src="{{ $logoDataUri }}" alt="Bethel Logo">
            </td>
            <td class="header-content">
                <div class="hospital-title">{{ $hospitalName }}</div>
                <div class="hospital-address">{{ $hospitalAddress }}</div>
                <div class="report-title-row">DEPRECIATION REPORT</div>
                <div class="report-meta">
                    As of {{ $asOfDate->format('F d, Y') }} &nbsp;|&nbsp;
                    Prepared by @<span style="font-weight: bold;">{{ $username }}</span> &nbsp;|&nbsp;
                    Generated {{ now()->format('Y-m-d h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Policy and Disclaimer Row --}}
    <div class="policy-box">
        Policy: Straight-Line Method | {{ $conventionLabel }} | Minimum Cost to Depreciate: {{ $minCostText }} &nbsp;|&nbsp;
        <em>Estimated values for tracking, not an official accounting record</em>
    </div>

    {{-- Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Asset No.</th>
                <th style="width: 16%;">Asset Description</th>
                <th style="width: 11%;">Category</th>
                <th style="width: 9%;">Dept</th>
                <th style="width: 5%;">Year</th>
                <th style="width: 7%;">Start Date</th>
                <th style="width: 5%;">Mos</th>
                <th style="width: 6%;">Life</th>
                <th style="width: 8%;">Cost</th>
                <th style="width: 7%;">Disposal</th>
                <th style="width: 8%;">Accum. Dep.</th>
                <th style="width: 8%;">Net Value</th>
            </tr>
        </thead>
        <tbody>
            @php $hasZeroCost = false; @endphp
            @forelse ($items as $index => $item)
                @php
                    if ($item['cost_raw'] === null) {
                        $hasZeroCost = true;
                    }
                @endphp
                <tr class="{{ $index % 2 === 1 ? 'row-even' : '' }}">
                    <td class="text-left font-mono" style="font-size: 6.8pt;">{{ $item['asset_no'] }}</td>
                    <td class="text-left">{{ $item['asset_name'] }}</td>
                    <td class="text-left">{{ $item['category'] }}</td>
                    <td class="text-left">{{ $item['department'] }}</td>
                    <td class="text-center">{{ $item['year_bought'] }}</td>
                    <td class="text-center">{{ $item['start_date'] }}</td>
                    <td class="text-center">{{ $item['months'] }}</td>
                    <td class="text-center">{{ $item['useful_life'] }}</td>
                    <td class="text-right {{ $item['cost_raw'] === null ? 'text-muted' : '' }}">
                        {{ $item['cost_formatted'] }}
                    </td>
                    <td class="text-right">{{ $item['salvage_formatted'] }}</td>
                    <td class="text-right {{ $item['accum_is_muted'] ? 'text-muted' : '' }}">
                        {{ $item['accum_formatted'] }}
                    </td>
                    <td class="text-right">{{ $item['current_formatted'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 15px; color: #64748b;">
                        No assets found matching the selected criteria.
                    </td>
                </tr>
            @endforelse

            {{-- Totals Row --}}
            @if (count($items) > 0)
                <tr class="totals-row">
                    <td colspan="8" class="text-right" style="padding-right: 8px;">TOTAL ASSET VALUE</td>
                    <td class="text-right">₱{{ number_format($totalCost, 2) }}</td>
                    <td class="text-right">₱{{ number_format($totalSalvage, 2) }}</td>
                    <td class="text-right">₱{{ number_format($totalAccumulated, 2) }}</td>
                    <td class="text-right">₱{{ number_format($totalCurrentValue, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if ($hasZeroCost)
        <div class="note-row">
            * Note: Assets marked "No cost entered" are excluded from total cost, accumulated depreciation, and current value sums.
        </div>
    @endif

    {{-- Hospital Clinical Signatures Block --}}
    <table class="signatures-table" style="width: 100%; margin-top: 24px; page-break-inside: avoid; border-collapse: collapse;">
        <tr>
            <td style="width: 33%; vertical-align: top; padding-right: 20px;">
                <div style="font-size: 7pt; color: #64748b; font-weight: bold; margin-bottom: 24px; letter-spacing: 0.3px;">PREPARED BY:</div>
                <div style="border-bottom: 1px solid #0f172a; width: 90%; height: 1px;"></div>
                <div style="font-size: 7.5pt; font-weight: bold; color: #0f172a; margin-top: 4px;">{{ $username }}</div>
                <div style="font-size: 6.8pt; color: #64748b;">Hospital Property Custodian</div>
            </td>
            <td style="width: 33%; vertical-align: top; padding-right: 20px;">
                <div style="font-size: 7pt; color: #64748b; font-weight: bold; margin-bottom: 24px; letter-spacing: 0.3px;">VERIFIED BY:</div>
                <div style="border-bottom: 1px solid #0f172a; width: 90%; height: 1px;"></div>
                <div style="font-size: 7.5pt; font-weight: bold; color: #0f172a; margin-top: 4px;">Chief Accountant / Finance</div>
                <div style="font-size: 6.8pt; color: #64748b;">Fiscal Services</div>
            </td>
            <td style="width: 34%; vertical-align: top;">
                <div style="font-size: 7pt; color: #64748b; font-weight: bold; margin-bottom: 24px; letter-spacing: 0.3px;">APPROVED BY:</div>
                <div style="border-bottom: 1px solid #0f172a; width: 90%; height: 1px;"></div>
                <div style="font-size: 7.5pt; font-weight: bold; color: #0f172a; margin-top: 4px;">Hospital Administrator</div>
                <div style="font-size: 6.8pt; color: #64748b;">Bethel Baptist Hospital, Inc.</div>
            </td>
        </tr>
    </table>
</body>
</html>
