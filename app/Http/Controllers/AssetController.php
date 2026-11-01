<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetNumberGenerator;
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

    public function show(Asset $asset): View
    {
        $asset->load(['transfers.mover']);

        return view('assets.show', [
            'asset' => $asset,
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

        $validated = $request->validate([
            'asset_no' => ['nullable', 'string', 'max:100', $uniqueRule],
            'asset_name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_no' => ['nullable', 'string', 'max:255'],
            'date_acquired' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(Asset::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'asset_no.unique' => 'This Asset No. is already used by another asset. Leave it blank to generate a new unique number automatically.',
            'asset_name.required' => 'Please enter the Asset Name (for example: Patient Monitor or Hospital Bed).',
            'date_acquired.date' => 'Please enter a valid Date Acquired, or leave it blank.',
            'status.required' => 'Please choose the current status of the asset.',
            'status.in' => 'Please choose a valid status from the list.',
        ]);

        foreach (['asset_no', 'model', 'serial_no', 'date_acquired', 'department', 'location', 'notes'] as $nullableField) {
            if (array_key_exists($nullableField, $validated)) {
                $trimmed = is_string($validated[$nullableField]) ? trim($validated[$nullableField]) : $validated[$nullableField];
                $validated[$nullableField] = ($trimmed === '' || $trimmed === null) ? null : $trimmed;
            }
        }

        $validated['asset_name'] = trim($validated['asset_name']);

        return $validated;
    }
}
