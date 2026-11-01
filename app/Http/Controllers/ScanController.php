<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        return view('scan.index', [
            'notFoundCode' => session('not_found_code'),
        ]);
    }

    public function lookup(Request $request)
    {
        $code = trim((string) $request->query('q', ''));

        if ($code === '') {
            return redirect()
                ->route('scan.index')
                ->withErrors(['q' => 'Please scan a barcode/QR tag or type an Asset No.']);
        }

        $asset = Asset::whereRaw('LOWER(asset_no) = LOWER(?)', [$code])->first();

        if ($asset) {
            return redirect()
                ->route('assets.show', $asset)
                ->with('success', "Asset \"{$asset->asset_no}\" ({$asset->asset_name}) opened.");
        }

        return redirect()
            ->route('scan.index')
            ->with('not_found_code', $code)
            ->withErrors([
                'q' => "No asset found with Asset No. \"{$code}\". Please check the code or register it as a new asset.",
            ]);
    }
}
