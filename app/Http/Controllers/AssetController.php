<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Services\AssetNumberGenerator;
use App\Services\DepreciationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
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

        $activeCount = Asset::count();
        $archivedCount = Asset::onlyTrashed()->count();

        $query = ($tab === 'archived')
            ? Asset::onlyTrashed()->with('category')
            : Asset::query()->with('category');

        $assets = $query
            ->when($search !== '', function ($subQuery) use ($search) {
                $like = '%' . $search . '%';
                $subQuery->where(function ($sub) use ($like) {
                    $sub->where('asset_no', 'like', $like)
                        ->orWhere('asset_name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhere('serial_no', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhere('department', 'like', $like)
                        ->orWhere('section', 'like', $like)
                        ->orWhere('assigned_personnel', 'like', $like)
                        ->orWhere('notes', 'like', $like);
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
            ->when($category !== '', fn ($subQuery) => $subQuery->where('category_id', $category))
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
            'statuses' => Asset::STATUSES,
            'conditions' => Asset::CONDITIONS,
            'search' => $search,
            'selectedDepartment' => $department,
            'selectedSection' => $section,
            'selectedStatus' => $status,
            'selectedCondition' => $condition,
            'selectedCategory' => $category,
            'activeTab' => $tab,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
            'depreciationService' => app(DepreciationService::class),
        ]);
    }

    public function create(AssetNumberGenerator $generator): View
    {
        return view('assets.create', [
            'suggestedAssetNo' => $generator->generate(),
            'statuses' => Asset::STATUSES,
            'conditions' => Asset::CONDITIONS,
            'departments' => $this->getDistinctSuggestions('department'),
            'sections' => $this->getDistinctSuggestions('section'),
            'assignedPersonnel' => $this->getDistinctSuggestions('assigned_personnel'),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AssetNumberGenerator $generator): RedirectResponse
    {
        $validated = $this->validateAsset($request);

        if (empty($validated['asset_no'])) {
            $validated['asset_no'] = $generator->generate();
        }

        $asset = Asset::create($validated);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset \"{$asset->asset_name}\" ({$asset->asset_no}) has been added.");
    }

    public function show(Request $request, Asset $asset, DepreciationService $depreciationService): View
    {
        $asset->load(['transfers.mover', 'category']);

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

        return view('assets.edit', [
            'asset' => $asset,
            'statuses' => Asset::STATUSES,
            'conditions' => Asset::CONDITIONS,
            'departments' => $this->getDistinctSuggestions('department'),
            'sections' => $this->getDistinctSuggestions('section'),
            'assignedPersonnel' => $this->getDistinctSuggestions('assigned_personnel'),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Asset $asset, AssetNumberGenerator $generator): RedirectResponse
    {
        $validated = $this->validateAsset($request, $asset);

        if (empty($validated['asset_no'])) {
            $validated['asset_no'] = $asset->asset_no ?: $generator->generate();
        }

        $asset->update($validated);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset \"{$asset->asset_name}\" ({$asset->asset_no}) has been updated.");
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $name = $asset->asset_name;
        $no = $asset->asset_no;

        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('success', "Asset \"{$name}\" ({$no}) has been moved to the archive.");
    }

    public function restore(Request $request, Asset $asset): RedirectResponse
    {
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

        $uniqueRule = Rule::unique('assets', 'asset_no');
        if ($asset) {
            $uniqueRule->ignore($asset->id);
        }

        $rules = [
            'asset_no' => ['nullable', 'string', 'max:100', $uniqueRule],
            'asset_name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_no' => ['nullable', 'string', 'max:255'],
            'year_bought' => ['nullable', 'integer', 'digits:4', 'min:1950', 'max:' . (int) date('Y')],
            'date_acquired' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
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

        foreach (['asset_no', 'category_id', 'cost', 'useful_life_years', 'salvage_value', 'brand', 'model', 'serial_no', 'date_acquired', 'department', 'section', 'assigned_personnel', 'location', 'asset_condition', 'notes'] as $nullableField) {
            if (array_key_exists($nullableField, $validated)) {
                $trimmed = is_string($validated[$nullableField]) ? trim($validated[$nullableField]) : $validated[$nullableField];
                $validated[$nullableField] = ($trimmed === '' || $trimmed === null) ? null : $trimmed;
            }
        }

        $validated['asset_name'] = trim($validated['asset_name']);

        return $validated;
    }
}
