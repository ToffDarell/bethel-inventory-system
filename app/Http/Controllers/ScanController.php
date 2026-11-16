<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetNumberHistory;
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

        // Check for historical asset number match
        $historyMatch = AssetNumberHistory::whereRaw('LOWER(old_no) = LOWER(?)', [$code])
            ->orWhereRaw('LOWER(new_no) = LOWER(?)', [$code])
            ->first();

        if ($historyMatch && $historyMatch->asset) {
            return redirect()
                ->route('assets.show', $historyMatch->asset)
                ->with('info', "Asset \"{$historyMatch->asset->asset_no}\" ({$historyMatch->asset->asset_name}) opened via former number \"{$historyMatch->old_no}\" (Old number).");
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
            ->orWhere('brand', 'like', "%{$code}%")
            ->orWhere('model', 'like', "%{$code}%")
            ->orWhere('serial_no', 'like', "%{$code}%")
            ->orWhere('section', 'like', "%{$code}%")
            ->orWhere('assigned_personnel', 'like', "%{$code}%")
            ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$code}%"))
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

        // Check if matching asset is in the archive
        $archivedMatch = Asset::onlyTrashed()
            ->where(function ($q) use ($code) {
                $q->whereRaw('LOWER(asset_no) = LOWER(?)', [$code])
                    ->orWhereRaw('LOWER(asset_name) = LOWER(?)', [$code]);
            })
            ->first();

        if ($archivedMatch) {
            return redirect()
                ->route('assets.show', $archivedMatch)
                ->with('info', "Asset \"{$archivedMatch->asset_no}\" ({$archivedMatch->asset_name}) was found in the Archive.");
        }

        return redirect()
            ->route('assets.find')
            ->with('not_found_code', $code)
            ->withErrors([
                'q' => "No asset found with code \"{$code}\". Please check the code or register it as a new asset.",
            ]);
    }
}
