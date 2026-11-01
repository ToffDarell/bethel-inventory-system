<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Transfer;
use App\Models\User;
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
     * @return array{rows: array<int, array<string, mixed>>, valid_count: int, duplicate_count: int, invalid_count: int}
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
            ];
        }

        $headerRow = array_map(fn ($col) => $this->normalizeHeaderKey((string) $col), $rawRows[0]);

        $existingNumbers = Asset::withTrashed()
            ->pluck('asset_no')
            ->mapWithKeys(fn ($no) => [strtolower(trim((string) $no)) => true])
            ->all();

        $seenInSheet = [];
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
            $assetName = trim($assoc['asset_name'] ?? '');
            $model = $this->nullableString($assoc['model'] ?? '');
            $serialNo = $this->nullableString($assoc['serial_no'] ?? '');
            $dateAcquired = $this->parseDate($assoc['date_acquired'] ?? '');
            $department = $this->nullableString($assoc['department'] ?? '');
            $location = $this->nullableString($assoc['location'] ?? '');
            $status = $this->normalizeStatus($assoc['status'] ?? '');
            $notes = $this->nullableString($assoc['notes'] ?? '');

            $isDuplicate = false;
            $isValid = true;
            $statusLabel = 'Ready to Import';

            if ($assetName === '') {
                $isValid = false;
                $statusLabel = 'Missing Asset Name (Skipped)';
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
                'model' => $model,
                'serial_no' => $serialNo,
                'date_acquired' => $dateAcquired,
                'department' => $department,
                'location' => $location,
                'status' => $status,
                'notes' => $notes,
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
                        ->exists();

                    if ($alreadyExists) {
                        $skipped++;
                        continue;
                    }
                } else {
                    $assetNo = $this->generator->generate();
                }

                $asset = Asset::create([
                    'asset_no' => $assetNo,
                    'asset_name' => $row['asset_name'],
                    'model' => $row['model'] ?? null,
                    'serial_no' => $row['serial_no'] ?? null,
                    'date_acquired' => $row['date_acquired'] ?? null,
                    'department' => $row['department'] ?? null,
                    'location' => $row['location'] ?? null,
                    'status' => $this->normalizeStatus((string) ($row['status'] ?? 'In Use')),
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

    protected function normalizeHeaderKey(string $raw): string
    {
        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $raw), '_'));

        return match ($clean) {
            'asset_no', 'asset_number', 'property_no', 'tag_no' => 'asset_no',
            'asset_name', 'name', 'item_name', 'description' => 'asset_name',
            'model', 'brand', 'brand_model' => 'model',
            'serial_no', 'serial_number', 'serial' => 'serial_no',
            'date_acquired', 'acquired_date', 'acquisition_date', 'date' => 'date_acquired',
            'department', 'dept' => 'department',
            'location', 'room', 'station' => 'location',
            'status', 'condition' => 'status',
            'notes', 'remarks', 'note' => 'notes',
            default => $clean,
        };
    }

    protected function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
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
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            if (is_numeric($trimmed) && (float) $trimmed > 1000) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $trimmed))->format('Y-m-d');
            }

            return Carbon::parse($trimmed)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}
