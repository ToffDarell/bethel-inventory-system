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
                ->route('assets.find')
                ->withErrors(['q' => 'Please type an Asset No. or scan a tag code.']);
        }

        $asset = Asset::whereRaw('LOWER(asset_no) = LOWER(?)', [$code])->first();

        if ($asset) {
            return redirect()
                ->route('assets.show', $asset)
                ->with('success', "Asset \"{$asset->asset_no}\" ({$asset->asset_name}) opened.");
        }

        // Check for exact name match
        $nameMatch = Asset::whereRaw('LOWER(asset_name) = LOWER(?)', [$code])->first();
        if ($nameMatch) {
            return redirect()
                ->route('assets.show', $nameMatch)
                ->with('success', "Asset \"{$nameMatch->asset_no}\" ({$nameMatch->asset_name}) opened.");
        }

        // Check if query matches a single partial record
        $partialMatches = Asset::where('asset_name', 'like', "%{$code}%")
            ->orWhere('asset_no', 'like', "%{$code}%")
            ->get();

        if ($partialMatches->count() === 1) {
            $single = $partialMatches->first();
            return redirect()
                ->route('assets.show', $single)
                ->with('success', "Asset \"{$single->asset_no}\" ({$single->asset_name}) opened.");
        }

        if ($partialMatches->count() > 1) {
            return redirect()
                ->route('assets.index', ['search' => $code])
                ->with('info', "Found {$partialMatches->count()} equipment items matching \"{$code}\".");
        }

        return redirect()
            ->route('assets.find')
            ->with('not_found_code', $code)
            ->withErrors([
                'q' => "No asset found with code \"{$code}\". Please check the code or register it as a new asset.",
            ]);
    }
}
