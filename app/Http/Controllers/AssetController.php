<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\AssetTemplate;
use App\Models\Category;
use App\Models\Department;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\AssetNumberGenerator;
use App\Services\AssetNumberService;
use App\Services\DepartmentBackfillService;
use App\Services\DepreciationService;
use App\Services\SectionBackfillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request, DepreciationService $depreciationService): View
    {
        $tab = $request->query('tab', 'active');
        if (! in_array($tab, ['active', 'archived'], true)) {
            $tab = 'active';
        }

        $search = trim((string) $request->query('search', ''));
        $department = trim((string) $request->query('department', ''));
        $section = trim((string) $request->query('section', ''));
        $status = trim((string) $request->query('status', ''));
        $condition = trim((string) $request->query('condition', ''));
        $category = trim((string) $request->query('category', ''));
        $vendor = trim((string) $request->query('vendor', ''));
        $location = trim((string) $request->query('location', ''));
        $belowMinCost = $request->boolean('below_min_cost');
        $needsReprint = $request->boolean('needs_reprint') || $request->query('needs_reprint') === '1';

        $activeCount = Asset::count();
        $archivedCount = Asset::onlyTrashed()->count();

        $query = ($tab === 'archived')
            ? Asset::onlyTrashed()->with(['category', 'vendor'])
            : Asset::query()->with(['category', 'vendor']);

        $assets = $query
            ->when($needsReprint, fn ($subQuery) => $subQuery->where('needs_tag_reprint', true))
            ->when($search !== '', function ($subQuery) use ($search) {
                $like = '%' . $search . '%';
                $historyAssetIds = AssetNumberHistory::where('old_no', 'like', $like)->pluck('asset_id');
                $subQuery->where(function ($sub) use ($like, $historyAssetIds) {
                    $sub->where('asset_no', 'like', $like)
                        ->orWhereIn('id', $historyAssetIds)
                        ->orWhere('asset_name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhere('serial_no', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhere('department', 'like', $like)
                        ->orWhere('section', 'like', $like)
                        ->orWhere('assigned_personnel', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', $like));
                });
            })
            ->when($department !== '', fn ($subQuery) => $subQuery->where('department', $department))
            ->when($section !== '', fn ($subQuery) => $subQuery->where('section', $section))
            ->when($status !== '', fn ($subQuery) => $subQuery->where('status', $status))
            ->when($condition !== '', function ($subQuery) use ($condition) {
                if ($condition === 'Not set' || $condition === 'unset') {
                    $subQuery->whereNull('asset_condition');
                } else {
                    $subQuery->where('asset_condition', $condition);
                }
            })
            ->when($category !== '', function ($subQuery) use ($category) {
                if (in_array(strtolower($category), ['none', 'uncategorized', 'no_category'], true)) {
                    $subQuery->whereNull('category_id');
                } else {
                    $subQuery->where('category_id', $category);
                }
            })
            ->when($vendor !== '', function ($subQuery) use ($vendor) {
                if (in_array(strtolower($vendor), ['none', 'unset', 'unassigned'], true)) {
                    $subQuery->whereNull('vendor_id');
                } else {
                    $subQuery->where('vendor_id', $vendor);
                }
            })
            ->when($location !== '', function ($subQuery) use ($location) {
                if (in_array(strtolower($location), ['unassigned', 'unset', 'none', 'missing'], true)) {
                    $subQuery->where(function ($q) {
                        $q->whereNull('location')->orWhere('location', '');
                    });
                } else {
                    $subQuery->where('location', $location);
                }
            })
            ->when($belowMinCost && $request->user()?->isAdmin(), function ($subQuery) use ($depreciationService) {
                $minCost = $depreciationService->getMinCost();
                if ($minCost > 0) {
                    $subQuery->whereNotNull('cost')->where('cost', '<', $minCost);
                }
            })
            ->when($tab === 'archived', fn ($subQuery) => $subQuery->latest('deleted_at'), fn ($subQuery) => $subQuery->latest('id'))
            ->paginate(15)
            ->withQueryString();

        $departments = $this->getDistinctSuggestions('department');
        $sections = $this->getDistinctSuggestions('section');

        return view('assets.index', [
            'assets' => $assets,
            'departments' => $departments,
            'sections' => $sections,
            'categories' => Category::orderBy('name')->get(),
            'vendors' => Vendor::orderBy('name')->get(),
            'statuses' => Asset::STATUSES,
            'conditions' => Asset::CONDITIONS,
            'search' => $search,
            'selectedDepartment' => $department,
            'selectedSection' => $section,
            'selectedStatus' => $status,
            'selectedCondition' => $condition,
            'selectedCategory' => $category,
            'selectedVendor' => $vendor,
            'selectedLocation' => $location,
            'belowMinCost' => $belowMinCost,
            'activeTab' => $tab,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
            'depreciationService' => $depreciationService,
        ]);
    }

    public function create(AssetNumberGenerator $generator): View
    {
        $departments = Department::active()->orderBy('name')->get();
        if ($departments->isEmpty() && ! empty($this->getDistinctSuggestions('department'))) {
            app(DepartmentBackfillService::class)->runBackfill();
            $departments = Department::active()->orderBy('name')->get();
        }

        $sectionOptions = Section::active()->orderBy('name')->get();
        if ($sectionOptions->isEmpty() && Asset::whereNotNull('department_id')->whereNotNull('section')->where('section', '!=', '')->exists()) {
            app(SectionBackfillService::class)->runBackfill();
            $sectionOptions = Section::active()->orderBy('name')->get();
        }

        return view('assets.create', [
            'suggestedAssetNo' => $generator->generate(),
            'statuses' => Asset::STATUSES,
            'conditions' => Asset::CONDITIONS,
            'departments' => $departments,
            'sectionOptions' => $sectionOptions,
            'departmentSuggestions' => $this->getDistinctSuggestions('department'),
            'sections' => $this->getDistinctSuggestions('section'),
            'assignedPersonnel' => $this->getDistinctSuggestions('assigned_personnel'),
            'categories' => Category::orderBy('name')->get(),
            'vendors' => Vendor::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AssetNumberService $numberService): RedirectResponse
    {
        $validated = $this->validateAsset($request);

        if (empty($validated['asset_no'])) {
            if (! empty($validated['department_id'])) {
                $dept = Department::find($validated['department_id']);
                $sectionModel = ! empty($validated['section_id']) ? Section::find($validated['section_id']) : null;
                $includeSection = Setting::get('include_section_in_asset_no', '1') === '1';

                if ($dept) {
                    if (! $dept->code_confirmed) {
                        return back()
                            ->withInput()
                            ->withErrors(['department_id' => "The department code for '{$dept->name}' is not confirmed. Please confirm the code in Settings > Departments before generating automated numbers."]);
                    }
                    if ($includeSection && $sectionModel && ! $sectionModel->code_confirmed) {
                        return back()
                            ->withInput()
                            ->withErrors(['section_id' => "The section code for '{$sectionModel->name}' is not confirmed. Please confirm the code in Settings > Departments before generating automated numbers."]);
                    }
                    $validated['asset_no'] = $numberService->generateNextNumber($dept, $sectionModel ?? ($validated['section'] ?? null));
                }
            }
            if (empty($validated['asset_no'])) {
                $validated['asset_no'] = app(AssetNumberGenerator::class)->generate();
            }
        }

        // Auto-add or link to Asset Masterlist when creating an asset manually without selecting a template
        if (empty($validated['asset_template_id']) && ! empty($validated['asset_name'])) {
            $template = AssetTemplate::findDuplicate(
                $validated['asset_name'],
                $validated['brand'] ?? null,
                $validated['model'] ?? null
            );

            if (! $template) {
                $template = AssetTemplate::create([
                    'name' => $validated['asset_name'],
                    'brand' => $validated['brand'] ?? null,
                    'model' => $validated['model'] ?? null,
                    'category_id' => $validated['category_id'] ?? null,
                    'default_remarks' => $validated['notes'] ?? null,
                    'active' => true,
                ]);
            } elseif (! $template->category_id && ! empty($validated['category_id'])) {
                $template->update(['category_id' => $validated['category_id']]);
            }

            if ($template && $template->active) {
                $validated['asset_template_id'] = $template->id;
            }
        }

        $asset = Asset::create($validated);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset \"{$asset->asset_name}\" ({$asset->asset_no}) has been added.");
    }

    public function show(Request $request, Asset $asset, DepreciationService $depreciationService): View
    {
        $asset->load(['transfers.mover', 'category', 'vendor', 'assetTemplate', 'departmentRelation', 'numberHistories.user']);

        $depreciation = $request->user()?->isAdmin()
            ? $depreciationService->calculateForAsset($asset)
            : null;

        return view('assets.show', [
            'asset' => $asset,
            'depreciation' => $depreciation,
        ]);
    }

    public function edit(Asset $asset): View|RedirectResponse
    {
        if ($asset->trashed()) {
            return redirect()
                ->route('assets.show', $asset)
                ->with('warning', "Asset \"{$asset->asset_name}\" ({$asset->asset_no}) is archived and cannot be edited. Please restore the asset to active inventory first.");
        }

        $departments = Department::active()->orderBy('name')->get();
        if ($departments->isEmpty() && ! empty($this->getDistinctSuggestions('department'))) {
            app(DepartmentBackfillService::class)->runBackfill();
            $departments = Department::active()->orderBy('name')->get();
        }

        $sectionOptions = Section::active()->orderBy('name')->get();
        if ($sectionOptions->isEmpty() && Asset::whereNotNull('department_id')->whereNotNull('section')->where('section', '!=', '')->exists()) {
            app(SectionBackfillService::class)->runBackfill();
            $sectionOptions = Section::active()->orderBy('name')->get();
        }

        $vendors = Vendor::active()->orderBy('name')->get();
        if ($asset->vendor_id && ! $vendors->contains('id', $asset->vendor_id)) {
            $currentVendor = Vendor::find($asset->vendor_id);
            if ($currentVendor) {
                $vendors = $vendors->push($currentVendor)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            }
        }

        return view('assets.edit', [
            'asset' => $asset,
            'statuses' => Asset::STATUSES,
            'conditions' => Asset::CONDITIONS,
            'departments' => $departments,
            'sectionOptions' => $sectionOptions,
            'departmentSuggestions' => $this->getDistinctSuggestions('department'),
            'sections' => $this->getDistinctSuggestions('section'),
            'assignedPersonnel' => $this->getDistinctSuggestions('assigned_personnel'),
            'categories' => Category::orderBy('name')->get(),
            'vendors' => $vendors,
            'selectedTemplate' => $asset->asset_template_id ? AssetTemplate::with('category:id,name')->find($asset->asset_template_id) : null,
        ]);
    }

    public function update(Request $request, Asset $asset, AssetNumberService $numberService): RedirectResponse
    {
        // Optimistic locking check
        if ($request->filled('lock_version')) {
            $lockVersion = (int) $request->input('lock_version');
            if ($asset->updated_at && $asset->updated_at->timestamp > $lockVersion) {
                return back()
                    ->withInput()
                    ->withErrors(['asset_no' => 'Another user modified this asset since you opened the form. Please review the updated details and try again.']);
            }
        }

        $validated = $this->validateAsset($request, $asset);

        $oldDeptId = $asset->department_id !== null ? (int) $asset->department_id : null;
        $newDeptId = ! empty($validated['department_id']) ? (int) $validated['department_id'] : null;

        $newDept = $newDeptId ? Department::find($newDeptId) : null;

        $isSameDept = false;
        if ($oldDeptId !== null && $newDeptId !== null) {
            $isSameDept = ($oldDeptId === $newDeptId);
        } elseif ($oldDeptId === null && $newDeptId === null) {
            $isSameDept = true;
        } elseif ($newDept && ! empty($asset->department)) {
            $isSameDept = (strtolower(trim((string) $asset->department)) === strtolower(trim((string) $newDept->name)));
        }

        $deptChanged = ! $isSameDept && $newDept !== null;
        $reassignOnDeptChange = Setting::get('reassign_number_on_dept_change', '1') === '1';

        $numberAction = $request->input('department_number_action');
        if (! $numberAction && $request->has('paper_tag_number_action')) {
            $numberAction = $request->input('paper_tag_number_action');
        }

        if ($deptChanged && $reassignOnDeptChange && $newDept) {
            $matchesPattern = $numberService->matchesPattern($asset->asset_no, $asset->departmentRelation);
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
                    $sectionModel = ! empty($validated['section_id']) ? Section::find($validated['section_id']) : null;
                    $newNo = $numberService->generateNextNumber($newDept, $sectionModel ?? ($validated['section'] ?? null));
                    $oldNo = $asset->asset_no;
                    $reason = ! $matchesPattern && $isAdmin
                        ? "Department transferred to \"{$newDept->name}\" (Admin renumbered)"
                        : "Department transferred from \"{$asset->department}\" to \"{$newDept->name}\"";

                    AssetNumberHistory::create([
                        'asset_id' => $asset->id,
                        'old_no' => $oldNo,
                        'new_no' => $newNo,
                        'reason' => $reason,
                        'changed_by' => $request->user()?->id,
                        'changed_at' => now(),
                    ]);
                    $validated['asset_no'] = $newNo;
                    $validated['needs_tag_reprint'] = true;
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

        if (empty($validated['asset_no'])) {
            $validated['asset_no'] = $asset->asset_no;
        }

        $asset->update($validated);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset \"{$asset->asset_name}\" ({$asset->asset_no}) has been updated.");
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can archive hospital equipment.');

        $name = $asset->asset_name;
        $no = $asset->asset_no;

        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('success', "Asset \"{$name}\" ({$no}) has been moved to the archive.");
    }

    public function restore(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can restore archived hospital equipment.');

        $name = $asset->asset_name;
        $no = $asset->asset_no;

        $asset->restore();

        activity()
            ->performedOn($asset)
            ->causedBy($request->user())
            ->log("Asset {$no} ({$name}) was restored from archive");

        if ($request->input('redirect_to') === 'show') {
            return redirect()
                ->route('assets.show', $asset)
                ->with('success', "Asset \"{$name}\" ({$no}) has been restored to active inventory.");
        }

        return redirect()
            ->route('assets.index', ['tab' => 'archived'])
            ->with('success', "Asset \"{$name}\" ({$no}) has been restored to active inventory.");
    }

    public function forceDelete(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can permanently delete assets.');

        $name = $asset->asset_name;
        $no = $asset->asset_no;

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'asset_no' => $no,
                'asset_name' => $name,
                'category_id' => $asset->category_id,
                'department' => $asset->department,
                'location' => $asset->location,
            ])
            ->log("Asset {$no} ({$name}) was permanently deleted by admin");

        $asset->forceDelete();

        return redirect()
            ->route('assets.index', ['tab' => 'archived'])
            ->with('success', "Asset \"{$name}\" ({$no}) has been permanently deleted from the system.");
    }

    public function revertNumber(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can revert asset numbers.');

        $latestHistory = $asset->numberHistories()->first();
        if (! $latestHistory) {
            return redirect()
                ->route('assets.show', $asset)
                ->withErrors(['asset_no' => 'No previous number history found to revert.']);
        }

        $previousNo = $latestHistory->old_no;
        $currentNo = $asset->asset_no;

        $isTaken = Asset::withTrashed()
            ->where('id', '!=', $asset->id)
            ->where('asset_no', $previousNo)
            ->exists();

        if ($isTaken) {
            return redirect()
                ->route('assets.show', $asset)
                ->withErrors(['asset_no' => "Cannot revert because previous number {$previousNo} is already assigned to another asset."]);
        }

        DB::transaction(function () use ($asset, $previousNo, $currentNo, $request) {
            $asset->update([
                'asset_no' => $previousNo,
                'needs_tag_reprint' => false,
            ]);

            AssetNumberHistory::create([
                'asset_id' => $asset->id,
                'old_no' => $currentNo,
                'new_no' => $previousNo,
                'reason' => 'Reverted by admin',
                'changed_by' => $request->user()?->id,
                'changed_at' => now(),
            ]);

            activity()
                ->performedOn($asset)
                ->causedBy($request->user())
                ->withProperties([
                    'from' => $currentNo,
                    'to' => $previousNo,
                    'reason' => 'Reverted by admin',
                ])
                ->log("Asset number reverted from {$currentNo} to {$previousNo} by admin");
        });

        $asset->load('departmentRelation');
        $dept = $asset->departmentRelation;
        $mismatchNote = null;
        if ($dept && ! empty($dept->code)) {
            $prefix = Setting::get('asset_no_prefix', 'BBH') . $dept->code;
            if (! str_starts_with($previousNo, $prefix)) {
                $mismatchNote = "Note: The reverted number ({$previousNo}) does not match current department code ({$dept->code}).";
            }
        }

        $successMsg = "Asset number reverted from {$currentNo} back to {$previousNo}.";
        if ($mismatchNote) {
            $successMsg .= " " . $mismatchNote;
        }

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', $successMsg);
    }

    public function bulkSetCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $assets = Asset::whereIn('id', $validated['ids'])->get();
        $count = 0;
        foreach ($assets as $asset) {
            $asset->update(['category_id' => $category->id]);
            $count++;
        }

        if (function_exists('activity')) {
            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'count' => $count,
                    'asset_ids' => $validated['ids'],
                ])
                ->log("Set category to '{$category->name}' for {$count} assets");
        }

        $message = $count === 1
            ? "Category set to \"{$category->name}\" for 1 asset."
            : "Category set to \"{$category->name}\" for {$count} assets.";

        return redirect()
            ->back()
            ->with('success', $message);
    }

    public function bulkArchive(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can archive hospital equipment.');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $assets = Asset::whereIn('id', $validated['ids'])->get();
        $count = 0;
        foreach ($assets as $asset) {
            $asset->delete();
            $count++;
        }

        $message = $count === 1
            ? '1 asset has been moved to the archive.'
            : "{$count} assets have been moved to the archive.";

        return redirect()
            ->route('assets.index')
            ->with('success', $message);
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can restore archived hospital equipment.');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $assets = Asset::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $count = 0;
        foreach ($assets as $asset) {
            $asset->restore();
            activity()
                ->performedOn($asset)
                ->causedBy($request->user())
                ->log("Asset {$asset->asset_no} ({$asset->asset_name}) was restored from archive");
            $count++;
        }

        $message = $count === 1
            ? '1 asset has been restored to active inventory.'
            : "{$count} assets have been restored to active inventory.";

        return redirect()
            ->route('assets.index', ['tab' => 'archived'])
            ->with('success', $message);
    }

    public function bulkForceDelete(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can permanently delete assets.');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $assets = Asset::withTrashed()->whereIn('id', $validated['ids'])->get();
        $count = 0;
        foreach ($assets as $asset) {
            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'asset_no' => $asset->asset_no,
                    'asset_name' => $asset->asset_name,
                    'category_id' => $asset->category_id,
                    'department' => $asset->department,
                    'location' => $asset->location,
                ])
                ->log("Asset {$asset->asset_no} ({$asset->asset_name}) was permanently deleted by admin");
            $asset->forceDelete();
            $count++;
        }

        $message = $count === 1
            ? '1 asset has been permanently deleted from the system.'
            : "{$count} assets have been permanently deleted from the system.";

        $tab = $request->input('tab', 'active');
        return redirect()
            ->route('assets.index', $tab === 'archived' ? ['tab' => 'archived'] : [])
            ->with('success', $message);
    }

    protected function getDistinctSuggestions(string $column): array
    {
        return Asset::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->pluck($column)
            ->map(fn ($val) => trim((string) $val))
            ->filter(fn ($val) => $val !== '')
            ->unique(fn ($val) => mb_strtolower($val))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    protected function validateAsset(Request $request, ?Asset $asset = null): array
    {
        if ($request->has('cost') && is_string($request->input('cost'))) {
            $cleaned = str_replace(',', '', trim($request->input('cost')));
            $request->merge(['cost' => $cleaned === '' ? null : $cleaned]);
        }

        if ($request->has('salvage_value') && is_string($request->input('salvage_value'))) {
            $cleaned = str_replace(',', '', trim($request->input('salvage_value')));
            $request->merge(['salvage_value' => $cleaned === '' ? null : $cleaned]);
        }

        if ($request->has('disposal_value') && is_string($request->input('disposal_value'))) {
            $cleaned = str_replace(',', '', trim($request->input('disposal_value')));
            $request->merge(['salvage_value' => $cleaned === '' ? null : $cleaned]);
        }

        $uniqueRule = Rule::unique('assets', 'asset_no');
        if ($asset) {
            $uniqueRule->ignore($asset->id);
        }

        $rules = [
            'asset_no' => [
                'nullable',
                'string',
                'max:100',
                $uniqueRule,
                function ($attribute, $value, $fail) use ($asset) {
                    if (! empty($value)) {
                        $inHistory = AssetNumberHistory::where('old_no', $value)
                            ->orWhere('new_no', $value)
                            ->when($asset, fn ($q) => $q->where('asset_id', '!=', $asset->id))
                            ->exists();
                        if ($inHistory) {
                            $fail('This Asset No. was previously assigned to another asset and cannot be reused.');
                        }
                    }
                },
            ],
            'asset_name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'department_id' => $request->has('department_id')
                ? ['required', 'exists:departments,id']
                : ['nullable', 'exists:departments,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'vendor_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($asset) {
                    if (! $value) {
                        return;
                    }
                    $vendor = Vendor::find($value);
                    if (! $vendor) {
                        $fail('The selected vendor does not exist.');

                        return;
                    }
                    if (! $vendor->active && (! $asset || (int) $asset->vendor_id !== (int) $vendor->id)) {
                        $fail('Inactive vendors cannot be selected for this asset.');
                    }
                },
            ],
            'asset_template_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($asset) {
                    if (! $value) {
                        return;
                    }
                    $template = AssetTemplate::find($value);
                    if (! $template) {
                        $fail('The selected asset template does not exist.');

                        return;
                    }
                    if (! $template->active && (! $asset || (int) $asset->asset_template_id !== (int) $template->id)) {
                        $fail('Inactive asset templates cannot be selected for new assets.');
                    }
                },
            ],
            'serial_no' => ['nullable', 'string', 'max:255'],
            'year_bought' => ['nullable', 'integer', 'digits:4', 'min:1950', 'max:' . (int) date('Y')],
            'date_acquired' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'section_id' => $request->has('section_id')
                ? [
                    'required',
                    'exists:sections,id',
                    function ($attribute, $value, $fail) use ($request) {
                        $deptId = (int) $request->input('department_id');
                        if ($deptId > 0 && ! empty($value)) {
                            $belongs = Section::where('id', (int) $value)->where('department_id', $deptId)->exists();
                            if (! $belongs) {
                                $fail('The selected section does not belong to the chosen department.');
                            }
                        }
                    },
                ]
                : ['nullable', 'exists:sections,id'],
            'section' => ($request->has('section') && ! $request->has('section_id'))
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'assigned_personnel' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(Asset::STATUSES)],
            'asset_condition' => $request->has('asset_condition')
                ? ['required', 'string', Rule::in(Asset::CONDITIONS)]
                : ['nullable', 'string', Rule::in(Asset::CONDITIONS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        // Monetary attributes can be viewed and edited by Admins only
        if ($request->user()?->isAdmin()) {
            $rules['cost'] = ['nullable', 'numeric', 'min:0', 'max:9999999999.99'];
            $rules['useful_life_years'] = ['nullable', 'integer', 'min:1', 'max:100'];
            $rules['salvage_value'] = ['nullable', 'numeric', 'min:0', 'max:9999999999.99'];
        }

        $validated = $request->validate($rules, [
            'asset_no.unique' => 'This Asset No. is already used by another asset. Leave it blank to generate a new unique number automatically.',
            'asset_name.required' => 'Please enter the Asset Description (for example: Patient Monitor or Hospital Bed).',
            'category_id.exists' => 'The selected category does not exist.',
            'department_id.required' => 'Please select a Department.',
            'department_id.exists' => 'The selected department does not exist.',
            'section_id.required' => 'Please select a Section.',
            'section_id.exists' => 'The selected section does not exist.',
            'section.required' => 'Please select or enter a Section.',
            'cost.numeric' => 'Acquisition cost must be a valid number.',
            'useful_life_years.integer' => 'Useful life must be a whole number of years.',
            'salvage_value.numeric' => 'Salvage value must be a valid number.',
            'year_bought.digits' => 'Year Bought must be a 4-digit year (for example: 2023).',
            'year_bought.min' => 'Year Bought must be 1950 or later.',
            'year_bought.max' => 'Year Bought cannot be in the future.',
            'date_acquired.date' => 'Please enter a valid Date Acquired, or leave it blank.',
            'status.required' => 'Please choose the current status of the asset.',
            'status.in' => 'Please choose a valid status from the list.',
            'asset_condition.required' => 'Please select an Asset Condition (Brand-new, Refurbish, Damage, or In Good Condition).',
            'asset_condition.in' => 'Please select a valid Asset Condition from the list.',
        ]);

        // Map year_bought to date_acquired
        if ($request->has('year_bought')) {
            $yearBoughtRaw = $request->input('year_bought');
            $yearBought = ($yearBoughtRaw !== null && $yearBoughtRaw !== '') ? (int) $yearBoughtRaw : null;
            if ($yearBought !== null) {
                $validated['acquired_year_only'] = true;
                if ($asset && $asset->date_acquired && (int) $asset->date_acquired->format('Y') === $yearBought) {
                    $validated['date_acquired'] = $asset->date_acquired->format('Y-m-d');
                } else {
                    $validated['date_acquired'] = sprintf('%04d-01-01', $yearBought);
                }
            } else {
                $validated['date_acquired'] = null;
                $validated['acquired_year_only'] = null;
            }
            unset($validated['year_bought']);
        }

        // Non-admin staff cannot submit or modify monetary fields under any circumstances
        if (! $request->user()?->isAdmin()) {
            unset($validated['cost'], $validated['useful_life_years'], $validated['salvage_value']);
        }

        // When updating, preserve existing useful life and salvage if not provided in the request
        if ($asset && ! $request->has('useful_life_years')) {
            unset($validated['useful_life_years']);
        }
        if ($asset && ! $request->has('salvage_value') && ! $request->has('disposal_value')) {
            unset($validated['salvage_value']);
        }

        if (! empty($validated['department_id'])) {
            $dept = Department::find($validated['department_id']);
            if ($dept) {
                $validated['department'] = $dept->name;
            }
        } elseif (! empty($validated['department'])) {
            $dept = Department::where('name', $validated['department'])->first();
            if ($dept) {
                $validated['department_id'] = $dept->id;
            }
        }

        if (! empty($validated['section_id'])) {
            $sec = Section::find($validated['section_id']);
            if ($sec) {
                $validated['section'] = $sec->name;
            }
        } elseif (! empty($validated['section']) && ! empty($validated['department_id'])) {
            $sec = Section::where('department_id', $validated['department_id'])
                ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $validated['section']))])
                ->first();
            if ($sec) {
                $validated['section_id'] = $sec->id;
                $validated['section'] = $sec->name;
            }
        }

        foreach (['asset_no', 'category_id', 'department_id', 'section_id', 'vendor_id', 'asset_template_id', 'cost', 'useful_life_years', 'salvage_value', 'brand', 'model', 'serial_no', 'date_acquired', 'department', 'section', 'assigned_personnel', 'location', 'asset_condition', 'notes'] as $nullableField) {
            if (array_key_exists($nullableField, $validated)) {
                $trimmed = is_string($validated[$nullableField]) ? trim($validated[$nullableField]) : $validated[$nullableField];
                $validated[$nullableField] = ($trimmed === '' || $trimmed === null) ? null : $trimmed;
            }
        }

        $validated['asset_name'] = trim($validated['asset_name']);

        return $validated;
    }
}
