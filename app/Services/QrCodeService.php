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
     * Uses the uploaded logo in storage/app/public if available, or a clean default SVG medical cross emblem encoded as base64.
     */
    public function hospitalLogoBase64(): string
    {
        $logoPath = Setting::get('logo_path');

        if (! empty($logoPath)) {
            $candidates = [
                storage_path('app/public/' . ltrim($logoPath, '/\\')),
                public_path('storage/' . ltrim($logoPath, '/\\')),
                public_path(ltrim($logoPath, '/\\')),
            ];

            foreach ($candidates as $fullPath) {
                if (File::exists($fullPath) && File::isFile($fullPath)) {
                    $mime = File::mimeType($fullPath) ?: 'image/png';
                    $contents = File::get($fullPath);

                    return 'data:' . $mime . ';base64,' . base64_encode($contents);
                }
            }
        }

        // Default medical cross emblem SVG as base64 data URI
        $defaultSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">'
            . '<rect x="2" y="2" width="60" height="60" rx="10" fill="#1e40af" stroke="#1e3a8a" stroke-width="2"/>'
            . '<path d="M26 14h12v12h12v12H38v12H26V38H14V26h12V14z" fill="#ffffff"/>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($defaultSvg);
    }
}
