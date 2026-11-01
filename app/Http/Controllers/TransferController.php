<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function create(Asset $asset)
    {
        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('assets.move', [
            'asset' => $asset,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'to_location' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'to_location.required' => 'Please enter the new location for this asset.',
        ]);

        $fromLocation = $asset->location;
        $toLocation = trim($validated['to_location']);
        $newDepartment = isset($validated['department']) && trim($validated['department']) !== ''
            ? trim($validated['department'])
            : $asset->department;

        DB::transaction(function () use ($asset, $fromLocation, $toLocation, $newDepartment, $validated, $request) {
            Transfer::create([
                'asset_id' => $asset->id,
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'moved_by' => $request->user()?->id,
                'moved_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $asset->update([
                'location' => $toLocation,
                'department' => $newDepartment,
            ]);

            activity()
                ->performedOn($asset)
                ->causedBy($request->user())
                ->withProperties([
                    'from_location' => $fromLocation,
                    'to_location' => $toLocation,
                    'department' => $newDepartment,
                    'notes' => $validated['notes'] ?? null,
                ])
                ->log("Moved asset {$asset->asset_no} from \"" . ($fromLocation ?: 'Unassigned') . "\" to \"{$toLocation}\"");
        });

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset \"{$asset->asset_name}\" ({$asset->asset_no}) moved to \"{$toLocation}\".");
    }
}
