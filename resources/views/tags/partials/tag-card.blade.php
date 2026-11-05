@php
    $tagAssetService = $tagAssetService ?? app(\App\Services\TagAssetService::class);
    $showBarcode = isset($showBarcode) ? (bool) $showBarcode : $tagAssetService->showBarcode();
    $hospitalName = $hospitalName ?? \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
    $hospitalAddress = $hospitalAddress ?? \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    $rawWidth = (float) ($tagWidthCm ?? \App\Models\Setting::get('tag_width_cm', '10'));
    $rawHeight = (float) ($tagHeightCm ?? \App\Models\Setting::get('tag_height_cm', '6'));
    $tagWidthCm = $rawWidth > 0 ? rtrim(rtrim(number_format($rawWidth, 2, '.', ''), '0'), '.') : '10';
    $tagHeightCm = $rawHeight > 0 ? rtrim(rtrim(number_format($rawHeight, 2, '.', ''), '0'), '.') : '6';
    $logoDataUri = $logoDataUri ?? $tagAssetService->hospitalLogoBase64();
    $forPdf = $forPdf ?? false;

    $nameLen = mb_strlen($asset->asset_name);
    $nameFontSize = $nameLen > 34 ? '6.8pt' : ($nameLen > 22 ? '7.3pt' : '8pt');
@endphp

<div class="property-tag-card" style="width: {{ $tagWidthCm }}cm; height: {{ $tagHeightCm }}cm; box-sizing: border-box; background: #ffffff; color: #000000; page-break-inside: avoid; break-inside: avoid; overflow: hidden; font-family: Arial, Helvetica, sans-serif;">
    <table style="width: 100%; height: 100%; border-collapse: collapse; border: 2px solid #000000; table-layout: fixed;">
        <!-- Header Row: Official BHB Logo + Hospital Name & Address -->
        <tr>
            <td colspan="2" style="border: 1px solid #000000; border-bottom: 2px solid #e60000; padding: 3px 6px; background-color: #ffffff; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 52px; vertical-align: middle; padding-right: 6px;">
                            <img src="{{ $logoDataUri }}" alt="Hospital Logo" style="width: 48px; height: 32px; object-fit: contain; display: block;">
                        </td>
                        <td style="vertical-align: middle; text-align: left;">
                            <div style="font-size: 9.5pt; font-weight: 700; line-height: 1.15; color: #000000; text-transform: uppercase;">
                                {{ $hospitalName }}
                            </div>
                            <div style="font-size: 6.5pt; line-height: 1.15; color: #000000;">
                                {{ $hospitalAddress }}
                            </div>
                        </td>
                        <td style="width: 90px; vertical-align: middle; text-align: right; padding-right: 4px;">
                            <span style="display: inline-block; background: #000000; color: #ffffff; padding: 2.5px 6px; font-size: 6.8pt; font-weight: 700; letter-spacing: 0.04em; white-space: nowrap; line-height: 1.15; border-radius: 2px;">
                                PROPERTY TAG
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Row 1: Date Acquired -->
        <tr>
            <td style="width: 28%; border: 1px solid #000000; padding: 2px 6px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Date Acquired
            </td>
            <td style="width: 72%; border: 1px solid #000000; padding: 2px 6px; font-size: 8pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $asset->date_acquired ? $asset->date_acquired->format('Y') : 'None' }}
            </td>
        </tr>

        <!-- Row 2: Asset Name -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Asset Name
            </td>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: {{ $nameFontSize }}; font-weight: 700; line-height: 1.18; word-break: break-word; white-space: normal;">
                {{ $asset->asset_name }}
            </td>
        </tr>

        <!-- Row 3: Model -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Model
            </td>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 8pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $asset->model ?: 'None' }}
            </td>
        </tr>

        <!-- Row 4: Serial No. -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Serial No.
            </td>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 8pt; font-family: Consolas, 'Courier New', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $asset->serial_no ?: 'None' }}
            </td>
        </tr>

        <!-- Row 5: Location -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Location
            </td>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 8pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $asset->location ?: ($asset->department ?: 'None') }}
            </td>
        </tr>

        <!-- Row 6: Asset No. -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Asset No.
            </td>
            <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 10.5pt; font-weight: 800; font-family: Consolas, 'Courier New', monospace; letter-spacing: 0.03em; color: #000000; white-space: nowrap;">
                {{ $asset->asset_no }}
            </td>
        </tr>

        @if ($showBarcode || !empty($preview))
            <!-- Full-Width Code 128 Barcode Strip -->
            <tr class="barcode-strip {{ $showBarcode ? '' : 'hidden' }}" style="{{ $showBarcode ? '' : 'display: none;' }}">
                <td colspan="2" style="border: 1px solid #000000; border-top: 2px solid #000000; padding: 4px 12px 3px 12px; background-color: #ffffff; text-align: center; vertical-align: middle;">
                    <div style="height: 13mm; max-height: 48px; display: block; margin: 0 auto; overflow: hidden;">
                        {!! $tagAssetService->barcodeSvg($asset->asset_no, 1.25, 42) !!}
                    </div>
                    <div style="font-family: Consolas, 'Courier New', monospace; font-size: 7.5pt; font-weight: 700; letter-spacing: 0.08em; color: #000000; margin-top: 1px; text-align: center;">
                        *{{ $asset->asset_no }}*
                    </div>
                </td>
            </tr>
        @endif
    </table>
</div>
