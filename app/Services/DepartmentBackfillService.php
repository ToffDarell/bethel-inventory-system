<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentBackfillService
{
    /**
     * Common hospital department name to code mappings.
     */
    protected const COMMON_CODES = [
        'emergency room' => 'ER',
        'emergency department' => 'ED',
        'emergency' => 'EMERG',
        'operating room' => 'OR',
        'intensive care unit' => 'ICU',
        'icu' => 'ICU',
        'biomedical engineering' => 'BIOMED',
        'biomedical' => 'BIOMED',
        'respiratory care' => 'RESP',
        'respiratory' => 'RESP',
        'radiology' => 'RAD',
        'laboratory' => 'LAB',
        'pharmacy' => 'PHARM',
        'pediatrics' => 'PED',
        'cardiology' => 'CARDIO',
        'outpatient department' => 'OPD',
        'outpatient' => 'OPD',
        'dialysis' => 'DIAL',
        'administration' => 'ADMIN',
        'central sterile supply' => 'CSSD',
        'surgery' => 'SURG',
        'obstetrics' => 'OBGYN',
        'nursing' => 'NURS',
    ];

    /**
     * Run backfill for all existing asset department strings.
     *
     * @return array{created: array, linked_assets_count: int, merge_list: array}
     */
    public function runBackfill(): array
    {
        $rawDepartments = Asset::withTrashed()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        $groups = [];
        foreach ($rawDepartments as $raw) {
            $cleaned = trim(preg_replace('/\s+/', ' ', (string) $raw));
            if ($cleaned === '') {
                continue;
            }
            $key = strtolower($cleaned);
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'original_variants' => [],
                    'canonical_name' => $cleaned,
                ];
            }
            if (! in_array($raw, $groups[$key]['original_variants'], true)) {
                $groups[$key]['original_variants'][] = trim($raw);
            }

            // Determine best display name (prefer uppercase acronym or Title Case)
            if (strtoupper($cleaned) === $cleaned && strlen($cleaned) <= 5) {
                $groups[$key]['canonical_name'] = $cleaned;
            } elseif (ctype_upper($cleaned[0]) && ! ctype_upper($groups[$key]['canonical_name'][0])) {
                $groups[$key]['canonical_name'] = $cleaned;
            }
        }

        $mergeList = [];
        $created = [];
        $linkedCount = 0;

        DB::transaction(function () use ($groups, &$mergeList, &$created, &$linkedCount) {
            foreach ($groups as $group) {
                $canonicalName = $group['canonical_name'];
                $variants = $group['original_variants'];
                $mergeList[$canonicalName] = $variants;

                $existingDept = Department::where('name', $canonicalName)->first();
                if (! $existingDept) {
                    $code = $this->suggestCode($canonicalName);
                    $dept = Department::create([
                        'name' => $canonicalName,
                        'code' => $code,
                        'next_number' => 1,
                        'active' => true,
                        'code_confirmed' => false, // Needs review by admin
                    ]);
                    $created[] = [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'code' => $dept->code,
                        'variants' => $variants,
                    ];
                } else {
                    $dept = $existingDept;
                }

                // Link assets matching any of the variants (case-insensitive)
                $updated = Asset::withTrashed()
                    ->where(function ($query) use ($variants, $canonicalName) {
                        foreach ($variants as $v) {
                            $query->orWhereRaw('LOWER(TRIM(department)) = ?', [strtolower(trim($v))]);
                        }
                    })
                    ->update([
                        'department_id' => $dept->id,
                        'department' => $dept->name,
                    ]);

                $linkedCount += $updated;
            }
        });

        return [
            'created' => $created,
            'linked_assets_count' => $linkedCount,
            'merge_list' => $mergeList,
        ];
    }

    /**
     * Suggest a clean, unique 2 to 6 character uppercase alphanumeric code.
     */
    public function suggestCode(string $name): string
    {
        $normalized = strtolower(trim($name));
        $cleanLetters = preg_replace('/[^A-Za-z0-9\s]/', '', $name);

        $code = null;
        if (isset(self::COMMON_CODES[$normalized])) {
            $code = self::COMMON_CODES[$normalized];
        } else {
            $words = array_values(array_filter(explode(' ', trim($cleanLetters))));
            if (count($words) >= 2) {
                // Acronym of first letters
                $acronym = '';
                foreach ($words as $w) {
                    $acronym .= strtoupper(substr($w, 0, 1));
                }
                if (strlen($acronym) >= 2 && strlen($acronym) <= 6) {
                    $code = $acronym;
                } elseif (strlen($acronym) > 6) {
                    $code = substr($acronym, 0, 6);
                } else {
                    $code = strtoupper(substr($words[0], 0, 3) . substr($words[1], 0, 1));
                }
            } else {
                // Single word
                $single = strtoupper($cleanLetters);
                if (strlen($single) <= 5) {
                    $code = $single;
                } else {
                    // Try consonants or first 4-5 chars
                    $code = substr($single, 0, 5);
                }
            }
        }

        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $code));
        if (strlen($code) < 2) {
            $code = str_pad($code, 2, 'X');
        }
        if (strlen($code) > 6) {
            $code = substr($code, 0, 6);
        }

        // Ensure uniqueness
        $baseCode = $code;
        $counter = 2;
        while (Department::where('code', $code)->exists()) {
            $suffix = (string) $counter;
            $allowedLength = 6 - strlen($suffix);
            $code = substr($baseCode, 0, max(1, $allowedLength)) . $suffix;
            $counter++;
        }

        return $code;
    }
}
