<?php

namespace App\Http\Controllers;

use App\Exports\AssetTemplatesSampleExport;
use App\Models\AssetTemplate;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AssetTemplateController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can manage asset templates.');
        }
    }

    /**
     * Live-search endpoint for the asset form masterlist picker (Staff & Admin).
     * Strictly returns IDs, names, and category name with no financial or depreciation values.
     */
    public function search(Request $request): JsonResponse
    {
        $query = AssetTemplate::normalizeNullable((string) $request->query('q', ''));

        $templates = AssetTemplate::query()
            ->with('category:id,name')
            ->where('active', true)
            ->when($query !== null, function ($q) use ($query) {
                $like = '%' . $query . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhereHas('category', function ($catQ) use ($like) {
                            $catQ->where('name', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->orderBy('brand')
            ->orderBy('model')
            ->limit(25)
            ->get()
            ->map(fn (AssetTemplate $template) => $template->toPickerArray())
            ->values();

        return response()->json($templates);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'default_remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Please enter an asset template name.',
            'category_id.exists' => 'The selected default category does not exist.',
        ]);

        $name = AssetTemplate::normalizeRequired($validated['name']);
        if ($name === '') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please enter a valid asset template name.',
                    'errors' => ['name' => ['Please enter a valid asset template name.']],
                ], 422);
            }

            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->withInput()
                ->withErrors(['template_name' => 'Please enter a valid asset template name.']);
        }

        $brand = AssetTemplate::normalizeNullable($validated['brand'] ?? null);
        $model = AssetTemplate::normalizeNullable($validated['model'] ?? null);
        $defaultRemarks = AssetTemplate::normalizeNullable($validated['default_remarks'] ?? null);

        if (AssetTemplate::findDuplicate($name, $brand, $model)) {
            $dupMessage = 'An asset template with this name, brand, and model already exists.';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $dupMessage,
                    'errors' => ['name' => [$dupMessage]],
                ], 422);
            }

            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->withInput()
                ->withErrors(['template_name' => $dupMessage]);
        }

        $template = AssetTemplate::create([
            'name' => $name,
            'brand' => $brand,
            'model' => $model,
            'category_id' => ! empty($validated['category_id']) ? (int) $validated['category_id'] : null,
            'default_remarks' => $defaultRemarks,
            'active' => true,
        ]);

        $template->load('category:id,name');

        activity()
            ->causedBy($request->user())
            ->performedOn($template)
            ->withProperties([
                'name' => $template->name,
                'brand' => $template->brand,
                'model' => $template->model,
                'category_id' => $template->category_id,
            ])
            ->log("Asset template '{$template->name}' was created");

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Asset template \"{$template->name}\" added to masterlist.",
                'template' => $template->toPickerArray(),
            ], 201);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'asset-templates'])
            ->with('success', "Asset template \"{$template->name}\" has been added.");
    }

    public function update(Request $request, AssetTemplate $assetTemplate): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'default_remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Please enter an asset template name.',
            'category_id.exists' => 'The selected default category does not exist.',
        ]);

        $name = AssetTemplate::normalizeRequired($validated['name']);
        if ($name === '') {
            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->withInput()
                ->withErrors(['template_name' => 'Please enter a valid asset template name.']);
        }

        $brand = AssetTemplate::normalizeNullable($validated['brand'] ?? null);
        $model = AssetTemplate::normalizeNullable($validated['model'] ?? null);
        $defaultRemarks = AssetTemplate::normalizeNullable($validated['default_remarks'] ?? null);

        if (AssetTemplate::findDuplicate($name, $brand, $model, $assetTemplate->id)) {
            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->withInput()
                ->withErrors(['template_name' => 'Another asset template with this name, brand, and model already exists.']);
        }

        $oldName = $assetTemplate->name;
        $assetTemplate->update([
            'name' => $name,
            'brand' => $brand,
            'model' => $model,
            'category_id' => ! empty($validated['category_id']) ? (int) $validated['category_id'] : null,
            'default_remarks' => $defaultRemarks,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($assetTemplate)
            ->withProperties([
                'old_name' => $oldName,
                'name' => $assetTemplate->name,
                'brand' => $assetTemplate->brand,
                'model' => $assetTemplate->model,
                'category_id' => $assetTemplate->category_id,
            ])
            ->log("Asset template '{$oldName}' was updated to '{$assetTemplate->name}'");

        return redirect()
            ->route('settings.index', ['tab' => 'asset-templates'])
            ->with('success', "Asset template \"{$assetTemplate->name}\" has been updated.");
    }

    public function deactivate(Request $request, AssetTemplate $assetTemplate): RedirectResponse
    {
        $this->ensureAdmin($request);

        $assetTemplate->update(['active' => false]);

        activity()
            ->causedBy($request->user())
            ->performedOn($assetTemplate)
            ->log("Asset template '{$assetTemplate->name}' was deactivated");

        return redirect()
            ->route('settings.index', ['tab' => 'asset-templates'])
            ->with('success', "Asset template \"{$assetTemplate->name}\" was deactivated.");
    }

    public function reactivate(Request $request, AssetTemplate $assetTemplate): RedirectResponse
    {
        $this->ensureAdmin($request);

        $assetTemplate->update(['active' => true]);

        activity()
            ->causedBy($request->user())
            ->performedOn($assetTemplate)
            ->log("Asset template '{$assetTemplate->name}' was reactivated");

        return redirect()
            ->route('settings.index', ['tab' => 'asset-templates'])
            ->with('success', "Asset template \"{$assetTemplate->name}\" was reactivated.");
    }

    public function destroy(Request $request, AssetTemplate $assetTemplate): RedirectResponse
    {
        $this->ensureAdmin($request);

        $assetCount = $assetTemplate->assets()->withTrashed()->count();
        if ($assetCount > 0) {
            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->with('error', "Cannot delete \"{$assetTemplate->name}\" because {$assetCount} asset(s) use it. Deactivate it instead.");
        }

        $name = $assetTemplate->name;
        activity()
            ->causedBy($request->user())
            ->performedOn($assetTemplate)
            ->log("Asset template '{$name}' was deleted");

        $assetTemplate->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'asset-templates'])
            ->with('success', "Asset template \"{$name}\" has been deleted.");
    }

    public function importSample(Request $request)
    {
        $this->ensureAdmin($request);

        return Excel::download(new AssetTemplatesSampleExport, 'asset-templates-sample.xlsx');
    }

    public function importPreview(Request $request): View|RedirectResponse
    {
        $this->ensureAdmin($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Please choose an Excel or CSV file to upload.',
            'file.mimes' => 'Please upload a valid .xlsx, .xls, or .csv file.',
        ]);

        try {
            $sheets = Excel::toArray(new class implements ToArray
            {
                public function array(array $array): void {}
            }, $request->file('file'));
        } catch (Throwable $e) {
            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->withErrors(['template_import' => 'Unable to read the spreadsheet file: ' . $e->getMessage()]);
        }

        $rawRows = $sheets[0] ?? [];
        if (count($rawRows) <= 1) {
            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->withErrors(['template_import' => 'The uploaded spreadsheet contains no template rows.']);
        }

        $headerRow = array_map(fn ($col) => $this->normalizeImportHeader((string) $col), $rawRows[0]);

        $categoriesByKey = Category::all()->mapWithKeys(
            fn (Category $cat) => [mb_strtolower(trim($cat->name), 'UTF-8') => $cat]
        );
        $existingKeys = AssetTemplate::pluck('unique_key')
            ->mapWithKeys(fn ($k) => [$k => true])
            ->all();
        $seenInFile = [];

        $parsedRows = [];
        $validCount = 0;
        $duplicateCount = 0;
        $invalidCount = 0;
        $unknownCategoryCount = 0;

        foreach (array_slice($rawRows, 1) as $index => $rawRow) {
            $assoc = [];
            foreach ($headerRow as $colIndex => $key) {
                if ($key !== '') {
                    $assoc[$key] = isset($rawRow[$colIndex]) ? trim((string) $rawRow[$colIndex]) : '';
                }
            }

            if (! collect($assoc)->contains(fn ($v) => $v !== '')) {
                continue;
            }

            $name = AssetTemplate::normalizeRequired($assoc['name'] ?? '');
            $brand = AssetTemplate::normalizeNullable($assoc['brand'] ?? null);
            $model = AssetTemplate::normalizeNullable($assoc['model'] ?? null);
            $categoryRaw = AssetTemplate::normalizeNullable($assoc['category'] ?? null);
            $defaultRemarks = AssetTemplate::normalizeNullable($assoc['default_remarks'] ?? null);

            $categoryId = null;
            $categoryDisplay = null;
            $isUnknownCategory = false;
            if ($categoryRaw !== null) {
                $catKey = mb_strtolower($categoryRaw, 'UTF-8');
                if ($categoriesByKey->has($catKey)) {
                    $cat = $categoriesByKey->get($catKey);
                    $categoryId = $cat->id;
                    $categoryDisplay = $cat->name;
                } else {
                    $isUnknownCategory = true;
                    $categoryDisplay = $categoryRaw;
                    $unknownCategoryCount++;
                }
            }

            $isDuplicate = false;
            $isValid = true;
            $statusLabel = 'Ready to Import';

            if ($name === '') {
                $isValid = false;
                $statusLabel = 'Missing Name (Skipped)';
                $invalidCount++;
            } else {
                $uniqueKey = AssetTemplate::buildUniqueKey($name, $brand, $model);
                if (isset($existingKeys[$uniqueKey]) || isset($seenInFile[$uniqueKey])) {
                    $isDuplicate = true;
                    $isValid = false;
                    $statusLabel = 'Duplicate (Skipped)';
                    $duplicateCount++;
                } else {
                    $seenInFile[$uniqueKey] = true;
                    $validCount++;
                }
            }

            $parsedRows[] = [
                'row_number' => $index + 2,
                'name' => $name,
                'brand' => $brand,
                'model' => $model,
                'category_id' => $categoryId,
                'category_display' => $categoryDisplay,
                'is_unknown_category' => $isUnknownCategory,
                'default_remarks' => $defaultRemarks,
                'is_duplicate' => $isDuplicate,
                'is_valid' => $isValid,
                'status_label' => $statusLabel,
            ];
        }

        $preview = [
            'rows' => $parsedRows,
            'valid_count' => $validCount,
            'duplicate_count' => $duplicateCount,
            'invalid_count' => $invalidCount,
            'unknown_category_count' => $unknownCategoryCount,
        ];

        $request->session()->put('asset_templates_import_preview', $preview);

        return view('settings.asset-templates-import-preview', $preview);
    }

    public function importCommit(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $preview = $request->session()->get('asset_templates_import_preview');
        if (! is_array($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('settings.index', ['tab' => 'asset-templates'])
                ->withErrors(['template_import' => 'No previewed asset templates were found. Please upload the spreadsheet again.']);
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($preview, $request, &$imported, &$skipped) {
            foreach ($preview['rows'] as $row) {
                if (empty($row['is_valid']) || ! empty($row['is_duplicate'])) {
                    $skipped++;
                    continue;
                }

                $name = AssetTemplate::normalizeRequired((string) ($row['name'] ?? ''));
                $brand = AssetTemplate::normalizeNullable($row['brand'] ?? null);
                $model = AssetTemplate::normalizeNullable($row['model'] ?? null);

                if ($name === '' || AssetTemplate::findDuplicate($name, $brand, $model)) {
                    $skipped++;
                    continue;
                }

                // Never auto-create categories: use resolved category_id only if it exists
                $categoryId = null;
                if (! empty($row['category_id'])) {
                    $cat = Category::find((int) $row['category_id']);
                    if ($cat) {
                        $categoryId = $cat->id;
                    }
                }

                AssetTemplate::create([
                    'name' => $name,
                    'brand' => $brand,
                    'model' => $model,
                    'category_id' => $categoryId,
                    'default_remarks' => AssetTemplate::normalizeNullable($row['default_remarks'] ?? null),
                    'active' => true,
                ]);
                $imported++;
            }

            if ($imported > 0) {
                activity()
                    ->causedBy($request->user())
                    ->withProperties([
                        'imported' => $imported,
                        'skipped' => $skipped,
                    ])
                    ->log("Imported {$imported} asset template(s) from Excel (skipped {$skipped})");
            }
        });

        $request->session()->forget('asset_templates_import_preview');

        return redirect()
            ->route('settings.index', ['tab' => 'asset-templates'])
            ->with('success', "Imported {$imported} asset template(s) to the masterlist (skipped {$skipped}).");
    }

    private function normalizeImportHeader(string $raw): string
    {
        $clean = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $raw), '_'));

        return match ($clean) {
            'name', 'asset_name', 'template_name', 'equipment_name', 'asset_description', 'description' => 'name',
            'brand', 'manufacturer' => 'brand',
            'model', 'model_no' => 'model',
            'category', 'category_name', 'default_category' => 'category',
            'default_remarks', 'remarks', 'notes', 'note' => 'default_remarks',
            default => $clean,
        };
    }
}
