@php
    $qrService = $qrService ?? app(\App\Services\QrCodeService::class);
    $hospitalName = $hospitalName ?? \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
    $hospitalAddress = $hospitalAddress ?? \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    $rawWidth = (float) ($tagWidthCm ?? \App\Models\Setting::get('tag_width_cm', '10'));
    $rawHeight = (float) ($tagHeightCm ?? \App\Models\Setting::get('tag_height_cm', '6'));
    $tagWidthCm = $rawWidth > 0 ? rtrim(rtrim(number_format($rawWidth, 2, '.', ''), '0'), '.') : '10';
    $tagHeightCm = $rawHeight > 0 ? rtrim(rtrim(number_format($rawHeight, 2, '.', ''), '0'), '.') : '6';
    $logoDataUri = $logoDataUri ?? $qrService->hospitalLogoBase64();
    $forPdf = $forPdf ?? false;
@endphp

<div class="property-tag-card" style="width: {{ $tagWidthCm }}cm; height: {{ $tagHeightCm }}cm; box-sizing: border-box; background: #ffffff; color: #000000; page-break-inside: avoid; break-inside: avoid; overflow: hidden; font-family: Arial, Helvetica, sans-serif;">
    <table style="width: 100%; height: 100%; border-collapse: collapse; border: 2px solid #000000; table-layout: fixed;">
        <!-- Header Row: Official BHB Logo + Hospital Name & Address -->
        <tr>
            <td colspan="3" style="border: 1px solid #000000; border-bottom: 2px solid #dc2626; padding: 3px 6px; background-color: #ffffff;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 52px; vertical-align: middle; padding-right: 6px;">
                            <img src="{{ $logoDataUri }}" alt="Hospital Logo" style="width: 48px; height: 32px; object-fit: contain; display: block;">
                        </td>
                        <td style="vertical-align: middle; text-align: left;">
                            <div style="font-size: 9.5pt; font-weight: 700; line-height: 1.15; color: #000000; text-transform: uppercase;">
                                {{ $hospitalName }}
                            </div>
                            <div style="font-size: 6.5pt; line-height: 1.15; color: #171717;">
                                {{ $hospitalAddress }}
                            </div>
                        </td>
                        <td style="width: 68px; vertical-align: middle; text-align: right;">
                            <span style="display: inline-block; border: 1px solid #000000; background: #0a0a0a; color: #ffffff; padding: 1px 4px; font-size: 6.2pt; font-weight: 700; letter-spacing: 0.04em;">
                                PROPERTY TAG
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Row 1: Date Acquired + QR Code (rowspan 6) -->
        <tr>
            <td style="width: 26%; border: 1px solid #000000; padding: 2px 5px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Date Acquired
            </td>
            <td style="width: 46%; border: 1px solid #000000; padding: 2px 5px; font-size: 8pt; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : '—' }}
            </td>
            <td rowspan="6" style="width: 28%; border: 1px solid #000000; padding: 3px; text-align: center; vertical-align: middle; background: #ffffff;">
                <div style="width: 100%; display: flex; align-items: center; justify-content: center;">
                    @if ($forPdf)
                        <img src="{{ $qrService->pngDataUri($asset->asset_no) }}" alt="QR {{ $asset->asset_no }}" style="width: 2.2cm; height: 2.2cm; display: block; margin: 0 auto;">
                    @else
                        <div class="qr-svg-box" style="width: 2.25cm; height: 2.25cm; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            {!! $qrService->svg($asset->asset_no) !!}
                        </div>
                    @endif
                </div>
                <div style="font-size: 5.8pt; font-weight: 700; margin-top: 2px; letter-spacing: 0.02em; color: #0a0a0a; word-break: break-all;">
                    SCAN QR
                </div>
            </td>
        </tr>

        <!-- Row 2: Asset Name -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Asset Name
            </td>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 8pt; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $asset->asset_name }}
            </td>
        </tr>

        <!-- Row 3: Model -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Model
            </td>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 8pt; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $asset->model ?: '—' }}
            </td>
        </tr>

        <!-- Row 4: Serial No. -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Serial No.
            </td>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 8pt; font-family: Consolas, monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $asset->serial_no ?: '—' }}
            </td>
        </tr>

        <!-- Row 5: Location -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Location
            </td>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 8pt; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $asset->location ?: ($asset->department ?: '—') }}
            </td>
        </tr>

        <!-- Row 6: Asset No. -->
        <tr>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 7.5pt; font-weight: 700; background-color: #fafafa; white-space: nowrap;">
                Asset No.
            </td>
            <td style="border: 1px solid #000000; padding: 2px 5px; font-size: 8.5pt; font-weight: 700; font-family: Consolas, monospace; letter-spacing: 0.02em; color: #b91c1c; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $asset->asset_no }}
            </td>
        </tr>
    </table>
</div>
