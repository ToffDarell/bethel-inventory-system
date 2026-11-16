<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\AssetTemplate;
use App\Models\Category;
use App\Models\Department;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class AssetImportService
{
    public function __construct(
        protected AssetNumberGenerator $generator
    ) {}

    /**
     * Parse an uploaded spreadsheet (.xlsx, .xls, .csv) and return a preview summary.
     *
     * @return array{rows: array<int, array<string, mixed>>, valid_count: int, duplicate_count: int, invalid_count: int, unknown_category_count: int}
     */
    public function preview(UploadedFile $file): array
    {
        $sheets = Excel::toArray(new class implements ToArray
        {
            public function array(array $array): void {}
        }, $file);

        $rawRows = $sheets[0] ?? [];
        if (count($rawRows) <= 1) {
            return [
                'rows' => [],
                'valid_count' => 0,
                'duplicate_count' => 0,
                'invalid_count' => 0,
                'unknown_category_count' => 0,
            ];
        }

        $headerRow = array_map(fn ($col) => $this->normalizeHeaderKey((string) $col), $rawRows[0]);

        $existingNumbers = Asset::withTrashed()
            ->pluck('asset_no')
            ->merge(AssetNumberHistory::pluck('old_no'))
            ->merge(AssetNumberHistory::pluck('new_no'))
            ->mapWithKeys(fn ($no) => [strtolower(trim((string) $no)) => true])
            ->all();

        $existingCategories = Category::pluck('name')
            ->mapWithKeys(fn ($name) => [strtolower(trim((string) $name)) => $name])
            ->all();

        $existingDepartments = Department::all()
            ->mapWithKeys(fn (Department $dept) => [strtolower(trim((string) preg_replace('/\s+/', ' ', $dept->name))) => $dept]);

        $allVendors = Vendor::orderBy('name')->get();
        $activeVendorsByKey = [];
        foreach ($allVendors as $v) {
            if ($v->active) {
                $activeVendorsByKey[Vendor::normalizedKey($v->name)] = $v;
            }
        }
        $activeTemplates = AssetTemplate::with('category:id,name')->where('active', true)->orderBy('name')->get();

        $seenInSheet = [];
        $unknownCategories = [];
        $newDepartments = [];
        $unknownVendorRows = [];
        $parsedRows = [];
        $validCount = 0;
        $duplicateCount = 0;
        $invalidCount = 0;

        foreach (array_slice($rawRows, 1) as $index => $rawRow) {
            $assoc = [];
            foreach ($headerRow as $colIndex => $key) {
                if ($key !== '') {
                    $assoc[$key] = isset($rawRow[$colIndex]) ? trim((string) $rawRow[$colIndex]) : '';
                }
            }

            // Skip completely blank rows
            $hasAnyValue = collect($assoc)->contains(fn ($val) => $val !== '');
            if (! $hasAnyValue) {
                continue;
            }

            $assetNo = trim($assoc['asset_no'] ?? '');
            $templateRaw = $this->nullableString($assoc['asset_template'] ?? '');
            $matchedTemplate = $templateRaw !== null ? AssetTemplate::findForImport($templateRaw, $activeTemplates) : null;
            $assetTemplateId = $matchedTemplate?->id;

            $assetName = trim($assoc['asset_name'] ?? '');
            if ($assetName === '' && $matchedTemplate) {
                $assetName = $matchedTemplate->name;
            }

            $categoryName = $this->nullableString($assoc['category'] ?? '');
            if ($categoryName === null && $matchedTemplate?->category) {
                $categoryName = $matchedTemplate->category->name;
            }

            $brand = $this->nullableString($assoc['brand'] ?? '') ?? $matchedTemplate?->brand;
            $model = $this->nullableString($assoc['model'] ?? '') ?? $matchedTemplate?->model;
            $serialNo = $this->nullableString($assoc['serial_no'] ?? '');
            $dateData = $this->parseDateWithYearOnly($assoc['date_acquired'] ?? '');
            $dateAcquired = $dateData[0];
            $acquiredYearOnly = $dateData[1];
            $department = $this->nullableString($assoc['department'] ?? '');
            $section = $this->nullableString($assoc['section'] ?? '');
            $location = $this->nullableString($assoc['location'] ?? '');
            $assignedPersonnel = $this->nullableString($assoc['assigned_personnel'] ?? '');
            $status = $this->normalizeStatus($assoc['status'] ?? '');
            $conditionData = $this->normalizeCondition($assoc['asset_condition'] ?? null);
            $notes = $this->nullableString($assoc['notes'] ?? '') ?? $matchedTemplate?->default_remarks;
            $vendorRaw = $this->nullableString($assoc['vendor'] ?? '');

            $cost = $this->parseMoney($assoc['cost'] ?? null);
            $salvageValue = $this->parseMoney($assoc['salvage_value'] ?? $assoc['disposal_value'] ?? null);
            $usefulLifeYears = $this->parseInteger($assoc['useful_life_years'] ?? null);

            $isUnknownCategory = false;
            if ($categoryName !== null) {
                $lowerCat = strtolower($categoryName);
                if (! isset($existingCategories[$lowerCat])) {
                    $isUnknownCategory = true;
                    $unknownCategories[$lowerCat] = $categoryName;
                }
            }

            $isNewDepartment = false;
            if ($department !== null) {
                $normDept = strtolower(trim((string) preg_replace('/\s+/', ' ', $department)));
                if (! $existingDepartments->has($normDept)) {
                    $isNewDepartment = true;
                    $newDepartments[$normDept] = $department;
                } else {
                    $department = $existingDepartments->get($normDept)->name;
                }
            }

            $vendorId = null;
            $vendorDisplay = null;
            $isUnknownVendor = false;
            $vendorNearMatches = [];
            if ($vendorRaw !== null) {
                $vendorNorm = Vendor::normalizeName($vendorRaw);
                $vendorKey = Vendor::normalizedKey($vendorNorm);
                if (isset($activeVendorsByKey[$vendorKey])) {
                    $matchedVendor = $activeVendorsByKey[$vendorKey];
                    $vendorId = $matchedVendor->id;
                    $vendorDisplay = $matchedVendor->name;
                } else {
                    $isUnknownVendor = true;
                    $vendorDisplay = $vendorNorm;
                    $vendorNearMatches = Vendor::findNearMatches($vendorNorm, $allVendors);
                    $unknownVendorRows[] = [
                        'row_number' => $index + 2,
                        'name' => $vendorNorm,
                        'near_matches' => array_column($vendorNearMatches, 'name'),
                    ];
                }
            }

            $isDuplicate = false;
            $isValid = true;
            $statusLabel = 'Ready to Import';

            if ($assetName === '') {
                $isValid = false;
                $statusLabel = 'Missing Asset Description (Skipped)';
                $invalidCount++;
            } elseif ($assetNo !== '') {
                $lowerNo = strtolower($assetNo);
                if (isset($existingNumbers[$lowerNo]) || isset($seenInSheet[$lowerNo])) {
                    $isDuplicate = true;
                    $isValid = false;
                    $statusLabel = 'Duplicate (Skipped)';
                    $duplicateCount++;
                } else {
                    $seenInSheet[$lowerNo] = true;
                    $validCount++;
                }
            } else {
                $validCount++;
            }

            $parsedRows[] = [
                'row_number' => $index + 2,
                'asset_no' => $assetNo,
                'asset_name' => $assetName,
                'category' => $categoryName,
                'is_unknown_category' => $isUnknownCategory,
                'cost' => $cost,
                'salvage_value' => $salvageValue,
                'useful_life_years' => $usefulLifeYears,
                'brand' => $brand,
                'model' => $model,
                'serial_no' => $serialNo,
                'date_acquired' => $dateAcquired,
                'acquired_year_only' => $acquiredYearOnly,
                'department' => $department,
                'section' => $section,
                'location' => $location,
                'assigned_personnel' => $assignedPersonnel,
                'status' => $status,
                'asset_condition' => $conditionData['value'],
                'condition_mapped' => $conditionData['mapped'],
                'condition_original' => $conditionData['original'],
                'is_unknown_condition' => $conditionData['is_unknown'],
                'condition_note' => $conditionData['note'],
                'notes' => $notes,
                'is_new_department' => $isNewDepartment,
                'vendor_id' => $vendorId,
                'vendor' => $vendorDisplay,
                'asset_template_id' => $assetTemplateId,
                'asset_template' => $matchedTemplate?->name ?? $templateRaw,
                'is_unknown_vendor' => $isUnknownVendor,
                'vendor_near_matches' => array_column($vendorNearMatches, 'name'),
                'is_duplicate' => $isDuplicate,
                'is_valid' => $isValid,
                'status_label' => $statusLabel,
            ];
        }

        return [
            'rows' => $parsedRows,
            'valid_count' => $validCount,
            'duplicate_count' => $duplicateCount,
            'invalid_count' => $invalidCount,
            'unknown_category_count' => count($unknownCategories),
            'new_department_count' => count($newDepartments),
            'unknown_vendor_count' => count($unknownVendorRows),
            'unknown_vendor_rows' => $unknownVendorRows,
        ];
    }

    /**
     * Commit previewed rows into the SQLite database, skipping duplicates and invalid rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{imported: int, skipped: int}
     */
    public function commit(array $rows, ?User $user = null): array
    {
        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $user, &$imported, &$skipped) {
            foreach ($rows as $row) {
                if (empty($row['is_valid']) || ! empty($row['is_duplicate'])) {
                    $skipped++;
                    continue;
                }

                $assetNo = trim((string) ($row['asset_no'] ?? ''));

                if ($assetNo !== '') {
                    $alreadyExists = Asset::withTrashed()
                        ->whereRaw('LOWER(asset_no) = LOWER(?)', [$assetNo])
                        ->exists()
                        || AssetNumberHistory::whereRaw('LOWER(old_no) = LOWER(?)', [$assetNo])
                        ->orWhereRaw('LOWER(new_no) = LOWER(?)', [$assetNo])
                        ->exists();

                    if ($alreadyExists) {
                        $skipped++;
                        continue;
                    }
                } else {
                    $assetNo = $this->generator->generate();
                }

                $departmentId = null;
                $departmentName = null;
                if (! empty($row['department'])) {
                    $cleanDept = trim((string) preg_replace('/\s+/', ' ', (string) $row['department']));
                    $normDept = strtolower($cleanDept);
                    $dept = Department::all()->first(
                        fn (Department $d) => strtolower(trim((string) preg_replace('/\s+/', ' ', $d->name))) === $normDept
                    );
                    if (! $dept) {
                        $suggestedCode = app(DepartmentBackfillService::class)->suggestCode($cleanDept);
                        $dept = Department::create([
                            'name' => $cleanDept,
                            'code' => $suggestedCode,
                            'next_number' => 1,
                            'active' => true,
                            'code_confirmed' => false,
                        ]);
                        activity()
                            ->causedBy($user)
                            ->performedOn($dept)
                            ->log("Department '{$dept->name}' ({$dept->code}) auto-created during import (needs review)");
                    }
                    $departmentId = $dept->id;
                    $departmentName = $dept->name;
                }

                $vendorId = null;
                if (! empty($row['vendor'])) {
                    $cleanVendor = Vendor::normalizeName((string) $row['vendor']);
                    if ($cleanVendor !== '') {
                        $vendor = Vendor::findByNormalizedName($cleanVendor, activeOnly: true)
                            ?? Vendor::findByNormalizedName($cleanVendor, activeOnly: false);
                        if (! $vendor) {
                            $vendor = Vendor::create([
                                'name' => $cleanVendor,
                                'active' => true,
                                'is_donation' => false,
                            ]);
                            activity()
                                ->causedBy($user)
                                ->performedOn($vendor)
                                ->log("Vendor '{$vendor->name}' created during Excel import");
                        }
                        $vendorId = $vendor->id;
                    }
                }

                $categoryId = null;
                if (! empty($row['category'])) {
                    $category = Category::firstOrCreate(
                        ['name' => trim((string) $row['category'])],
                        ['default_salvage_percent' => null, 'disposal_reviewed' => false]
                    );
                    $categoryId = $category->id;
                }

                $cost = isset($row['cost']) && $row['cost'] !== null && $row['cost'] !== '' ? (float) $row['cost'] : null;
                $salvageValue = isset($row['salvage_value']) && $row['salvage_value'] !== null && $row['salvage_value'] !== '' ? (float) $row['salvage_value'] : null;
                $usefulLifeYears = isset($row['useful_life_years']) && $row['useful_life_years'] !== null && $row['useful_life_years'] !== '' ? (int) $row['useful_life_years'] : null;

                $asset = Asset::create([
                    'asset_no' => $assetNo,
                    'asset_name' => $row['asset_name'],
                    'brand' => $row['brand'] ?? null,
                    'model' => $row['model'] ?? null,
                    'vendor_id' => $vendorId,
                    'asset_template_id' => ! empty($row['asset_template_id']) ? (int) $row['asset_template_id'] : null,
                    'serial_no' => $row['serial_no'] ?? null,
                    'category_id' => $categoryId,
                    'department_id' => $departmentId,
                    'department' => $departmentName ?? ($row['department'] ?? null),
                    'section' => $row['section'] ?? null,
                    'location' => $row['location'] ?? null,
                    'assigned_personnel' => $row['assigned_personnel'] ?? null,
                    'date_acquired' => $row['date_acquired'] ?? null,
                    'acquired_year_only' => (bool) ($row['acquired_year_only'] ?? false),
                    'status' => $this->normalizeStatus((string) ($row['status'] ?? 'In Use')),
                    'asset_condition' => $row['asset_condition'] ?? null,
                    'cost' => $cost,
                    'salvage_value' => $salvageValue,
                    'useful_life_years' => $usefulLifeYears,
                    'notes' => $row['notes'] ?? null,
                ]);

                if (! empty($asset->location)) {
                    Transfer::create([
                        'asset_id' => $asset->id,
                        'from_location' => null,
                        'to_location' => $asset->location,
                        'moved_by' => $user?->id,
                        'moved_at' => now(),
                        'notes' => 'Imported from Excel',
                    ]);
                }

                $imported++;
            }

            if ($imported > 0) {
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'imported' => $imported,
                        'skipped' => $skipped,
                    ])
                    ->log("Imported {$imported} asset(s) from Excel (skipped {$skipped}).");
            }
        });

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    public function normalizeHeaderKey(string $raw): string
    {
        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $raw), '_'));

        return match ($clean) {
            'asset_no', 'asset_number', 'property_no', 'tag_no' => 'asset_no',
            'asset_description', 'asset_name', 'name', 'item_name', 'description' => 'asset_name',
            'category', 'category_name', 'asset_category' => 'category',
            'brand' => 'brand',
            'model', 'brand_model' => 'model',
            'serial_no', 'serial_number', 'serial' => 'serial_no',
            'year_bought', 'year', 'year_acquired', 'date_acquired', 'acquired_date', 'acquisition_date', 'date' => 'date_acquired',
            'department', 'dept' => 'department',
            'section' => 'section',
            'assigned_personnel', 'personnel', 'assigned_to', 'assigned_staff' => 'assigned_personnel',
            'location', 'room', 'station' => 'location',
            'asset_condition', 'condition' => 'asset_condition',
            'status' => 'status',
            'cost', 'purchase_cost', 'acquisition_cost', 'price' => 'cost',
            'useful_life_years', 'useful_life', 'life_years', 'life' => 'useful_life_years',
            'disposal_value', 'disposal', 'disposal_val', 'salvage_value', 'salvage', 'scrap_value', 'residual_value' => 'salvage_value',
            'remarks', 'notes', 'note' => 'notes',
            'vendor', 'vendor_name', 'supplier', 'supplier_name' => 'vendor',
            'asset_template', 'template', 'masterlist', 'equipment_template' => 'asset_template',
            default => $clean,
        };
    }

    public function normalizeCondition(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [
                'value' => null,
                'mapped' => false,
                'original' => null,
                'is_unknown' => false,
                'note' => null,
            ];
        }

        $trimmed = trim($raw);
        $lower = strtolower($trimmed);

        // Exact allowed values: Brand-new, Refurbish, Damage, In Good Condition
        foreach (Asset::CONDITIONS as $allowed) {
            if (strcasecmp($trimmed, $allowed) === 0) {
                $mapped = ($trimmed !== $allowed);

                return [
                    'value' => $allowed,
                    'mapped' => $mapped,
                    'original' => $trimmed,
                    'is_unknown' => false,
                    'note' => $mapped ? "will be saved as {$allowed}" : null,
                ];
            }
        }

        // Near-match mappings
        $nearMatches = [
            'brand new' => 'Brand-new',
            'brandnew' => 'Brand-new',
            'new' => 'Brand-new',
            'refurbished' => 'Refurbish',
            'refurb' => 'Refurbish',
            'damaged' => 'Damage',
            'broken' => 'Damage',
            'good condition' => 'In Good Condition',
            'good' => 'In Good Condition',
            'in good' => 'In Good Condition',
        ];

        if (isset($nearMatches[$lower])) {
            $mappedValue = $nearMatches[$lower];

            return [
                'value' => $mappedValue,
                'mapped' => true,
                'original' => $trimmed,
                'is_unknown' => false,
                'note' => "will be saved as {$mappedValue}",
            ];
        }

        return [
            'value' => null,
            'mapped' => false,
            'original' => $trimmed,
            'is_unknown' => true,
            'note' => "Unknown condition '{$trimmed}' (will be saved as Not set)",
        ];
    }

    protected function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function parseMoney(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(str_ireplace(['₱', 'php', ',', ' '], '', $value));
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        $val = round((float) $clean, 2);

        return $val >= 0 ? $val : null;
    }

    protected function parseInteger(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $clean = trim($value);
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        $val = (int) $clean;

        return $val >= 0 ? $val : null;
    }

    protected function normalizeStatus(string $value): string
    {
        $trimmed = trim($value);

        foreach (Asset::STATUSES as $allowed) {
            if (strcasecmp($trimmed, $allowed) === 0) {
                return $allowed;
            }
        }

        return 'In Use';
    }

    protected function parseDate(string $value): ?string
    {
        return $this->parseDateWithYearOnly($value)[0];
    }

    protected function parseDateWithYearOnly(string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [null, null];
        }

        // 4-digit plain year: 1950 to 2100 (e.g. 2023 -> 2023-01-01, year-only = true)
        if (preg_match('/^\d{4}$/', $trimmed)) {
            $year = (int) $trimmed;
            if ($year >= 1900 && $year <= 2100) {
                return ["{$year}-01-01", true];
            }
        }

        try {
            if (is_numeric($trimmed) && (float) $trimmed > 20000) {
                return [Carbon::instance(ExcelDate::excelToDateTimeObject((float) $trimmed))->format('Y-m-d'), false];
            }

            return [Carbon::parse($trimmed)->format('Y-m-d'), false];
        } catch (Throwable) {
            return [null, null];
        }
    }
}
