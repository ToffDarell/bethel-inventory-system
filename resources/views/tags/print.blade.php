<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Property Tags ({{ $assets->count() }}) - Bethel Asset Tracker</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.6cm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #0f172a;
        }

        .toolbar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .toolbar-title {
            font-size: 16px;
            font-weight: 700;
        }

        .toolbar-subtitle {
            font-size: 13px;
            color: #cbd5e1;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #334155;
            color: #ffffff;
            border: 1px solid #475569;
        }

        .btn-secondary:hover {
            background-color: #475569;
        }

        .sheet-wrapper {
            padding: 24px;
            display: flex;
            justify-content: center;
        }

        .tags-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4cm;
            justify-content: flex-start;
            align-content: flex-start;
            background: #ffffff;
            padding: 0.6cm;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            min-height: 27cm;
            width: 21cm;
        }

        .qr-svg-box svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        @media print {
            body {
                background-color: #ffffff;
            }

            .no-print {
                display: none !important;
            }

            .sheet-wrapper {
                padding: 0;
                display: block;
            }

            .tags-grid {
                box-shadow: none;
                padding: 0;
                width: 100%;
                min-height: auto;
                gap: 0.3cm;
            }
        }
    </style>
</head>
<body>
    @php
        $queryString = http_build_query(['ids' => $assets->pluck('id')->all()]);
    @endphp

    <div class="toolbar no-print">
        <div class="toolbar-left">
            <div>
                <div class="toolbar-title">
                    Property Tag Print Preview ({{ $assets->count() }} {{ Str::plural('tag', $assets->count()) }})
                </div>
                <div class="toolbar-subtitle">
                    Tag size: {{ $tagWidthCm }} cm &times; {{ $tagHeightCm }} cm &bull; Click "Print Now" to open the printer dialog
                </div>
            </div>
        </div>

        <div class="toolbar-actions">
            @if ($assets->count() === 1)
                <a href="{{ route('assets.show', $assets->first()) }}" class="btn btn-secondary">
                    &larr; Back to Asset
                </a>
            @else
                <a href="{{ route('assets.index') }}" class="btn btn-secondary">
                    &larr; Back to Assets
                </a>
            @endif

            <a href="{{ route('tags.pdf') . '?' . $queryString }}" class="btn btn-secondary">
                Download PDF
            </a>

            <button type="button" onclick="window.print()" class="btn btn-primary">
                Print Now
            </button>
        </div>
    </div>

    <div class="sheet-wrapper">
        <div class="tags-grid">
            @foreach ($assets as $asset)
                @include('tags.partials.tag-card', [
                    'asset' => $asset,
                    'hospitalName' => $hospitalName,
                    'hospitalAddress' => $hospitalAddress,
                    'tagWidthCm' => $tagWidthCm,
                    'tagHeightCm' => $tagHeightCm,
                    'logoDataUri' => $logoDataUri,
                    'qrService' => $qrService,
                    'forPdf' => false,
                ])
            @endforeach
        </div>
    </div>
</body>
</html>
