<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\Department;
use App\Models\Setting;
use App\Models\Transfer;
use App\Services\AssetNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function create(Asset $asset)
    {
        $departments = Department::active()->orderBy('name')->get();
        if ($departments->isEmpty()) {
            $departments = Asset::query()
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->orderBy('department')
                ->get();
        }

        return view('assets.move', [
            'asset' => $asset,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request, Asset $asset, AssetNumberService $numberService)
    {
        $validated = $request->validate([
            'to_location' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'department' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'paper_tag_number_action' => ['nullable', 'in:keep,assign_new'],
        ], [
            'to_location.required' => 'Please enter the new location for this asset.',
        ]);

        $fromLocation = $asset->location;
        $toLocation = trim($validated['to_location']);

        $newDept = null;
        if (! empty($validated['department_id'])) {
            $newDept = Department::find($validated['department_id']);
        } elseif (! empty($validated['department'])) {
            $newDept = Department::where('name', trim($validated['department']))->first();
        }

        $newDepartmentName = $newDept ? $newDept->name : (isset($validated['department']) && trim($validated['department']) !== '' ? trim($validated['department']) : $asset->department);
        $newDeptId = $newDept ? (int) $newDept->id : ($asset->department_id !== null ? (int) $asset->department_id : null);
        $oldDeptId = $asset->department_id !== null ? (int) $asset->department_id : null;

        $isSameDept = false;
        if ($oldDeptId !== null && $newDeptId !== null) {
            $isSameDept = ($oldDeptId === $newDeptId);
        } elseif ($oldDeptId === null && $newDeptId === null) {
            $isSameDept = true;
        } elseif ($newDept && ! empty($asset->department)) {
            $isSameDept = (strtolower(trim((string) $asset->department)) === strtolower(trim((string) $newDept->name)));
        }

        $reassignEnabled = Setting::get('reassign_number_on_dept_change', '1') === '1';
        $numberAction = $request->input('department_number_action');
        if (! $numberAction && $request->has('paper_tag_number_action')) {
            $numberAction = $request->input('paper_tag_number_action');
        }

        DB::transaction(function () use ($asset, $fromLocation, $toLocation, $newDepartmentName, $newDeptId, $newDept, $isSameDept, $reassignEnabled, $numberAction, $validated, $request, $numberService) {
            Transfer::create([
                'asset_id' => $asset->id,
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'moved_by' => $request->user()?->id,
                'moved_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $assetUpdate = [
                'location' => $toLocation,
                'department' => $newDepartmentName,
                'department_id' => $newDeptId,
            ];

            if (! $isSameDept && $reassignEnabled && $newDept) {
                $matchesPattern = $numberService->matchesPattern($asset->asset_no, $asset->departmentRelation);
                $oldNo = $asset->asset_no;
                $isAdmin = (bool) $request->user()?->isAdmin();

                if ($numberAction === 'keep') {
                    activity()
                        ->performedOn($asset)
                        ->causedBy($request->user())
                        ->log("Asset {$asset->asset_no} department changed to {$newDept->name} while keeping current number");
                } elseif ($numberAction === 'assign_new' || ($matchesPattern && $numberAction === null)) {
                    if (! $matchesPattern && ! $isAdmin) {
                        activity()
                            ->performedOn($asset)
                            ->causedBy($request->user())
                            ->log("Asset {$asset->asset_no} department changed to {$newDept->name} while retaining legacy paper number");
                    } else {
                        $newNo = $numberService->generateNextNumber($newDept, $asset->sectionRelation ?? $asset->section);
                        $reason = ! $matchesPattern && $isAdmin
                            ? "Department moved to {$newDept->name} (Admin renumbered)"
                            : "Department moved to {$newDept->name}";

                        AssetNumberHistory::create([
                            'asset_id' => $asset->id,
                            'old_no' => $oldNo,
                            'new_no' => $newNo,
                            'reason' => $reason,
                            'changed_by' => $request->user()?->id,
                            'changed_at' => now(),
                        ]);
                        $assetUpdate['asset_no'] = $newNo;
                        $assetUpdate['needs_tag_reprint'] = true;
                        session()->flash('reassigned_new_no', $newNo);
                        session()->flash('reassigned_old_no', $oldNo);
                    }
                } else {
                    activity()
                        ->performedOn($asset)
                        ->causedBy($request->user())
                        ->log("Asset {$asset->asset_no} department changed to {$newDept->name} while retaining legacy paper number");
                }
            }

            $asset->update($assetUpdate);

            activity()
                ->performedOn($asset)
                ->causedBy($request->user())
                ->withProperties([
                    'from_location' => $fromLocation,
                    'to_location' => $toLocation,
                    'department' => $newDepartmentName,
                    'notes' => $validated['notes'] ?? null,
                ])
                ->log("Moved asset {$asset->asset_no} from \"" . ($fromLocation ?: 'Unassigned') . "\" to \"{$toLocation}\"");
        });

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset \"{$asset->asset_name}\" ({$asset->asset_no}) moved to \"{$toLocation}\".");
    }
}
