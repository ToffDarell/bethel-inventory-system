<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Setting;
use App\Services\TagAssetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(
        protected TagAssetService $tagAssetService
    ) {}

    public function print(Request $request)
    {
        $assets = $this->resolveAssets($request);

        if ($assets->isEmpty()) {
            return redirect()
                ->route('assets.index')
                ->withErrors(['ids' => 'Please select at least one asset to print property tags.']);
        }

        return view('tags.print', $this->buildViewData($assets, false));
    }

    public function pdf(Request $request)
    {
        $assets = $this->resolveAssets($request);

        if ($assets->isEmpty()) {
            return redirect()
                ->route('assets.index')
                ->withErrors(['ids' => 'Please select at least one asset to download PDF tags.']);
        }

        $pdf = Pdf::loadView('tags.pdf', $this->buildViewData($assets, true))
            ->setPaper('a4', 'portrait');

        $filename = $assets->count() === 1
            ? 'property-tag-' . preg_replace('/[^A-Za-z0-9\-_]/', '-', $assets->first()->asset_no) . '.pdf'
            : 'property-tags-' . now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }

    protected function resolveAssets(Request $request)
    {
        $rawIds = $request->input('ids', $request->input('id', []));
        $ids = is_array($rawIds) ? $rawIds : [$rawIds];
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return collect();
        }

        return Asset::whereIn('id', $ids)
            ->orderBy('asset_no')
            ->get();
    }

    protected function buildViewData($assets, bool $forPdf): array
    {
        $rawWidth = (float) Setting::get('tag_width_cm', '10');
        $rawHeight = (float) Setting::get('tag_height_cm', '6');

        $widthCm = $rawWidth > 0 ? rtrim(rtrim(number_format($rawWidth, 2, '.', ''), '0'), '.') : '10';
        $heightCm = $rawHeight > 0 ? rtrim(rtrim(number_format($rawHeight, 2, '.', ''), '0'), '.') : '6';

        return [
            'assets' => $assets,
            'hospitalName' => Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.'),
            'hospitalAddress' => Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
            'tagWidthCm' => $widthCm,
            'tagHeightCm' => $heightCm,
            'showBarcode' => Setting::get('show_barcode', '0') === '1',
            'logoDataUri' => $this->tagAssetService->hospitalLogoBase64(),
            'tagAssetService' => $this->tagAssetService,
            'forPdf' => $forPdf,
        ];
    }
}
