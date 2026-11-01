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
            background-color: #fff5f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #171717;
        }

        .toolbar {
            background-color: #ffffff;
            border-bottom: 3px solid #e60000;
            color: #171717;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 8px rgba(230, 0, 0, 0.08);
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .toolbar-logo {
            background: #ffffff;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 4px 8px;
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .toolbar-title {
            font-size: 15px;
            font-weight: 800;
            color: #e60000;
            letter-spacing: -0.01em;
        }

        .toolbar-subtitle {
            font-size: 12px;
            color: #525252;
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
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #e60000;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #cc0000;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #e60000;
            border: 2px solid #e60000;
        }

        .btn-secondary:hover {
            background-color: #fef2f2;
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
