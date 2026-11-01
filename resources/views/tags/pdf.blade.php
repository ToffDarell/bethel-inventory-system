<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property Tags PDF</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.6cm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            background: #ffffff;
        }

        .tag-item {
            display: inline-block;
            vertical-align: top;
            margin: 0 0.25cm 0.35cm 0;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div>
        @foreach ($assets as $asset)
            <div class="tag-item">
                @include('tags.partials.tag-card', [
                    'asset' => $asset,
                    'hospitalName' => $hospitalName,
                    'hospitalAddress' => $hospitalAddress,
                    'tagWidthCm' => $tagWidthCm,
                    'tagHeightCm' => $tagHeightCm,
                    'logoDataUri' => $logoDataUri,
                    'qrService' => $qrService,
                    'forPdf' => true,
                ])
            </div>
        @endforeach
    </div>
</body>
</html>
