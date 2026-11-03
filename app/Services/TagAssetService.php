<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Picqer\Barcode\BarcodeGeneratorSVG;

class TagAssetService
{
    /**
     * Determine whether the barcode strip is enabled on property tags.
     */
    public function showBarcode(): bool
    {
        return Setting::get('show_barcode', '0') === '1';
    }

    /**
     * Render a Code 128 barcode as inline SVG.
     *
     * @param  string  $code  Asset number to encode (e.g. BBH-000001, BBHIND-S300002315)
     * @param  float  $widthFactor  Width scaling factor for bars
     * @param  int  $totalHeight  Total barcode height in pixels (12-15mm range is approx 40-50px)
     * @return string Inline SVG markup
     */
    public function barcodeSvg(string $code, float $widthFactor = 1.3, int $totalHeight = 42): string
    {
        $generator = new BarcodeGeneratorSVG();
        $rawSvg = $generator->getBarcode($code, BarcodeGeneratorSVG::TYPE_CODE_128, $widthFactor, $totalHeight);

        // Strip XML declaration and DOCTYPE for direct HTML embedding
        $cleanSvg = preg_replace('/^<\?xml[^>]*\?>\s*/i', '', $rawSvg);
        $cleanSvg = preg_replace('/^<!DOCTYPE[^>]*>\s*/i', '', $cleanSvg);

        // Ensure crisp display and scaling
        $cleanSvg = preg_replace('/<svg /', '<svg preserveAspectRatio="xMidYMid meet" style="display:block;margin:0 auto;max-width:100%;height:100%;" ', $cleanSvg, 1);

        return $cleanSvg;
    }

    /**
     * Return the hospital logo as a base64 data URI.
     * Uses the uploaded logo in storage/app/public if available, or the official Bethel Baptist Hospital logo in public/images/bethel-logo.png.
     */
    public function hospitalLogoBase64(): string
    {
        $logoPath = Setting::get('logo_path');

        $candidates = [];

        if (! empty($logoPath)) {
            $candidates[] = storage_path('app/public/' . ltrim($logoPath, '/\\'));
            $candidates[] = public_path('storage/' . ltrim($logoPath, '/\\'));
            $candidates[] = public_path(ltrim($logoPath, '/\\'));
        }

        // Official Bethel Baptist Hospital, Inc. logo
        $candidates[] = public_path('images/bethel-logo.png');
        $candidates[] = storage_path('app/public/logos/bethel-logo.png');

        foreach ($candidates as $fullPath) {
            if (File::exists($fullPath) && File::isFile($fullPath)) {
                $mime = File::mimeType($fullPath) ?: 'image/png';
                $contents = File::get($fullPath);

                return 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
        }

        // Fallback SVG in Black & Red if file is ever removed
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">'
            . '<rect x="2" y="2" width="60" height="60" rx="10" fill="#111111" stroke="#dc2626" stroke-width="3"/>'
            . '<path d="M26 14h12v12h12v12H38v12H26V38H14V26h12V14z" fill="#dc2626"/>'
            . '</svg>'
        );
    }
}
