<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\Department;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;
use RuntimeException;
use Throwable;

class AssetNumberService
{
    /**
     * Generate the next unique asset number for a department atomically with SQLite write lock.
     *
     * @throws RuntimeException If department or section code is not confirmed or max attempts exceeded.
     */
    public function generateNextNumber(Department $department, Section|string|null $section = null): string
    {
        if (! $department->code_confirmed) {
            throw new RuntimeException("The department code for '{$department->name}' is not confirmed. Please confirm the code in Settings > Departments before generating asset numbers.");
        }

        $includeSection = Setting::get('include_section_in_asset_no', '1') === '1';
        if ($includeSection && $section instanceof Section && ! $section->code_confirmed) {
            throw new RuntimeException("The section code for '{$section->name}' is not confirmed. Please confirm the code in Settings > Departments before generating asset numbers.");
        }

        $prefix = (string) Setting::get('asset_no_prefix', 'BBH');
        $digits = max(3, (int) Setting::get('asset_number_digits', 4));
        $scope = (string) Setting::get('asset_number_scope', 'department');
        $sectionCode = $includeSection ? $this->resolveSectionCode($section) : '';

        return $this->executeWithWriteLock(function () use ($department, $prefix, $digits, $scope, $sectionCode) {
            if ($scope === 'global') {
                return $this->allocateGlobalNumber($department, $prefix, $digits, $sectionCode);
            }

            return $this->allocateDepartmentNumber($department, $prefix, $digits, $sectionCode);
        });
    }

    /**
     * Preview the next unique asset number without allocating or incrementing the counter.
     */
    public function previewNextNumber(Department $department, Section|string|null $section = null): string
    {
        $prefix = (string) Setting::get('asset_no_prefix', 'BBH');
        $digits = max(3, (int) Setting::get('asset_number_digits', 4));
        $scope = (string) Setting::get('asset_number_scope', 'department');
        $deptCode = strtoupper(trim($department->code));
        $includeSection = Setting::get('include_section_in_asset_no', '1') === '1';
        $sectionCode = $includeSection ? $this->resolveSectionCode($section) : '';

        if ($scope === 'global') {
            $counter = max(1, (int) Setting::get('global_next_asset_number', 1));
        } else {
            $counter = max(1, (int) $department->next_number);
        }

        do {
            $candidate = $this->formatNumber($prefix, $deptCode, $sectionCode, $counter, $digits);
            $counter++;
            $exists = $this->isNumberTaken($candidate);
        } while ($exists);

        return $candidate;
    }

    /**
     * Resolve section code from a Section model or raw section string.
     */
    public function resolveSectionCode(Section|string|null $section): string
    {
        if ($section instanceof Section) {
            return strtoupper(trim((string) $section->code));
        }

        return $this->normalizeSectionCode($section);
    }

    /**
     * Normalize free-text section input (e.g. "ICU 2") into a compact uppercase code ("ICU2").
     */
    public function normalizeSectionCode(?string $section): string
    {
        $cleaned = preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim((string) $section)));

        return substr($cleaned ?? '', 0, 10);
    }

    /**
     * Format an asset number with an optional hyphen-separated section code.
     */
    protected function formatNumber(string $prefix, string $deptCode, string $sectionCode, int $counter, int $digits): string
    {
        if ($sectionCode !== '') {
            return sprintf('%s%s-%s-%0' . $digits . 'd', $prefix, $deptCode, $sectionCode, $counter);
        }

        return sprintf('%s%s-%0' . $digits . 'd', $prefix, $deptCode, $counter);
    }

    /**
     * Check if a given asset number matches the standard generated pattern.
     */
    public function matchesPattern(string $assetNo, ?Department $department = null): bool
    {
        $prefix = (string) Setting::get('asset_no_prefix', 'BBH');
        if ($department && ! empty($department->code)) {
            $expectedPrefix = $prefix . $department->code . '-';
            return str_starts_with($assetNo, $expectedPrefix)
                && preg_match('/^' . preg_quote($expectedPrefix, '/') . '(?:[A-Z0-9]{1,10}-)?\d+$/', $assetNo) === 1;
        }

        // Generic standard pattern: [PREFIX][DEPT_CODE]-(optional [SECTION]-)[DIGITS]
        return preg_match('/^[A-Z0-9]{3,12}-(?:[A-Z0-9]{1,10}-)?\d+$/', $assetNo) === 1;
    }

    /**
     * Allocate sequence number for department scope.
     */
    protected function allocateDepartmentNumber(Department $department, string $prefix, int $digits, string $sectionCode = ''): string
    {
        $dept = Department::where('id', $department->id)->lockForUpdate()->first();
        if (! $dept) {
            throw new RuntimeException("Department not found");
        }

        $deptCode = strtoupper(trim($dept->code));
        $nextNumber = max(1, (int) $dept->next_number);

        do {
            $candidate = $this->formatNumber($prefix, $deptCode, $sectionCode, $nextNumber, $digits);
            $nextNumber++;
            $exists = $this->isNumberTaken($candidate);
        } while ($exists);

        $dept->update(['next_number' => $nextNumber]);

        return $candidate;
    }

    /**
     * Allocate sequence number for global scope.
     */
    protected function allocateGlobalNumber(Department $department, string $prefix, int $digits, string $sectionCode = ''): string
    {
        $deptCode = strtoupper(trim($department->code));
        $globalCounter = max(1, (int) Setting::get('global_next_asset_number', 1));

        do {
            $candidate = $this->formatNumber($prefix, $deptCode, $sectionCode, $globalCounter, $digits);
            $globalCounter++;
            $exists = $this->isNumberTaken($candidate);
        } while ($exists);

        Setting::set('global_next_asset_number', (string) $globalCounter);

        return $candidate;
    }

    /**
     * Check if candidate number exists in active assets, archived assets, or history.
     */
    public function isNumberTaken(string $candidate): bool
    {
        if (Asset::withTrashed()->where('asset_no', $candidate)->exists()) {
            return true;
        }

        if (AssetNumberHistory::where('old_no', $candidate)->orWhere('new_no', $candidate)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Execute a closure inside a database transaction with an immediate write lock.
     * Retries automatically on SQLite busy/locked errors with jitter.
     */
    public function executeWithWriteLock(callable $callback, int $maxAttempts = 60)
    {
        $attempt = 0;

        while (true) {
            $attempt++;
            $connection = DB::connection();
            $isSqlite = $connection->getDriverName() === 'sqlite';

            try {
                if ($isSqlite) {
                    $pdo = $connection->getPdo();
                    if (! $pdo->inTransaction()) {
                        $pdo->exec('BEGIN IMMEDIATE TRANSACTION');
                        try {
                            $result = $callback();
                            $pdo->commit();
                            return $result;
                        } catch (Throwable $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            throw $e;
                        }
                    } else {
                        return $callback();
                    }
                } else {
                    return DB::transaction($callback);
                }
            } catch (PDOException|QueryException $e) {
                $msg = strtolower($e->getMessage());
                $isBusy = str_contains($msg, 'busy') || str_contains($msg, 'locked');

                if ($isBusy && $attempt < $maxAttempts) {
                    usleep(random_int(10000, 60000)); // 10ms - 60ms random jitter
                    continue;
                }

                throw $e;
            }
        }
    }
}
