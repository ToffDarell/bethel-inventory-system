<?php

namespace App\Services;

use App\Models\Setting;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\File;

class QrCodeService
{
    /**
     * Render an inline SVG QR code for browser and Electron print sheets.
     */
    public function svg(string $text): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'imageBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ]);

        $svg = (new QRCode($options))->render($text);

        return str_replace('<svg ', '<svg style="width:100%;height:100%;display:block;" ', $svg);
    }

    /**
     * Render a base64 SVG data URI QR code.
     */
    public function svgDataUri(string $text): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'imageBase64' => true,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ]);

        return (new QRCode($options))->render($text);
    }

    /**
     * Render a base64 PNG data URI QR code (ideal for DomPDF).
     */
    public function pngDataUri(string $text): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'imageBase64' => true,
            'eccLevel' => EccLevel::M,
            'scale' => 6,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ]);

        return (new QRCode($options))->render($text);
    }

    /**
     * Return the hospital logo as a base64 data URI (Adjustment #6).
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
        $defaultSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">'
            . '<rect x="2" y="2" width="60" height="60" rx="10" fill="#111111" stroke="#dc2626" stroke-width="3"/>'
            . '<path d="M26 14h12v12h12v12H38v12H26V38H14V26h12V14z" fill="#dc2626"/>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($defaultSvg);
    }
}
