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
        $search = trim((string) $request->query('search', ''));
        $department = trim((string) $request->query('department', ''));
        $status = trim((string) $request->query('status', ''));

        $assets = Asset::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('asset_no', 'like', $like)
                        ->orWhere('asset_name', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhere('serial_no', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhere('department', 'like', $like);
                });
            })
            ->when($department !== '', fn ($query) => $query->where('department', $department))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('assets.index', [
            'assets' => $assets,
            'departments' => $departments,
            'statuses' => Asset::STATUSES,
            'search' => $search,
            'selectedDepartment' => $department,
            'selectedStatus' => $status,
        ]);
    }

    public function create(AssetNumberGenerator $generator): View
    {
        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('assets.create', [
            'suggestedAssetNo' => $generator->generate(),
            'statuses' => Asset::STATUSES,
            'departments' => $departments,
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

    public function edit(Asset $asset): View
    {
        $departments = Asset::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('assets.edit', [
            'asset' => $asset,
            'statuses' => Asset::STATUSES,
            'departments' => $departments,
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
            ->with('success', "Asset \"{$name}\" ({$no}) has been deleted.");
    }

    protected function validateAsset(Request $request, ?Asset $asset = null): array
    {
        $uniqueRule = Rule::unique('assets', 'asset_no');
        if ($asset) {
            $uniqueRule->ignore($asset->id);
        }

        $rules = [
            'asset_no' => ['nullable', 'string', 'max:100', $uniqueRule],
            'asset_name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_no' => ['nullable', 'string', 'max:255'],
            'date_acquired' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(Asset::STATUSES)],
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
            'asset_name.required' => 'Please enter the Asset Name (for example: Patient Monitor or Hospital Bed).',
            'category_id.exists' => 'The selected category does not exist.',
            'cost.numeric' => 'Acquisition cost must be a valid number.',
            'useful_life_years.integer' => 'Useful life must be a whole number of years.',
            'salvage_value.numeric' => 'Salvage value must be a valid number.',
            'date_acquired.date' => 'Please enter a valid Date Acquired, or leave it blank.',
            'status.required' => 'Please choose the current status of the asset.',
            'status.in' => 'Please choose a valid status from the list.',
        ]);

        // Non-admin staff cannot submit or modify monetary fields under any circumstances
        if (! $request->user()?->isAdmin()) {
            unset($validated['cost'], $validated['useful_life_years'], $validated['salvage_value']);
        }

        foreach (['asset_no', 'category_id', 'cost', 'useful_life_years', 'salvage_value', 'model', 'serial_no', 'date_acquired', 'department', 'location', 'notes'] as $nullableField) {
            if (array_key_exists($nullableField, $validated)) {
                $trimmed = is_string($validated[$nullableField]) ? trim($validated[$nullableField]) : $validated[$nullableField];
                $validated[$nullableField] = ($trimmed === '' || $trimmed === null) ? null : $trimmed;
            }
        }

        $validated['asset_name'] = trim($validated['asset_name']);

        return $validated;
    }
}
