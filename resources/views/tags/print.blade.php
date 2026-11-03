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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Segoe UI Variable Text", Arial, sans-serif;
            color: #0f172a;
        }

        .toolbar {
            background-color: #ffffff;
            border-top: 3px solid #b91c1c;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .toolbar-logo {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .toolbar-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        .toolbar-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: #0f5d8c;
            color: #ffffff;
            border: 1px solid #0f5d8c;
            box-shadow: 0 1px 2px rgba(15, 93, 140, 0.2);
        }

        .btn-primary:hover {
            background-color: #0c4a70;
            border-color: #0c4a70;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
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
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
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
            <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital, Inc." class="toolbar-logo">
            <div>
                <div class="toolbar-title">
                    Property Tag Print Preview ({{ $assets->count() }} {{ Str::plural('tag', $assets->count()) }})
                </div>
                <div class="toolbar-subtitle">
                    Tag size: {{ $tagWidthCm }} cm &times; {{ $tagHeightCm }} cm &bull; Click "Print Now" to open printer dialog
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
