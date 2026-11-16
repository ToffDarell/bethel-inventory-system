<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

class SectionBackfillService
{
    /**
     * Backfill sections per department from existing asset section text where section_id is null.
     *
     * @return array{created: array, linked_assets_count: int}
     */
    public function runBackfill(): array
    {
        $rows = Asset::withTrashed()
            ->whereNotNull('department_id')
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->get(['department_id', 'section'])
            ->unique(fn ($row) => $row->department_id . '|' . strtolower(trim(preg_replace('/\s+/', ' ', (string) $row->section))));

        $groups = [];
        foreach ($rows as $row) {
            $cleaned = trim(preg_replace('/\s+/', ' ', (string) $row->section));
            if ($cleaned === '') {
                continue;
            }
            $deptId = (int) $row->department_id;
            $key = $deptId . '|' . strtolower($cleaned);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'department_id' => $deptId,
                    'canonical_name' => $cleaned,
                    'variants' => [],
                ];
            }
            if (! in_array($row->section, $groups[$key]['variants'], true)) {
                $groups[$key]['variants'][] = trim((string) $row->section);
            }
        }

        $created = [];
        $linkedCount = 0;

        DB::transaction(function () use ($groups, &$created, &$linkedCount) {
            foreach ($groups as $group) {
                $deptId = $group['department_id'];
                $canonicalName = $group['canonical_name'];
                $variants = $group['variants'];

                $section = Section::where('department_id', $deptId)
                    ->whereRaw('LOWER(name) = ?', [strtolower($canonicalName)])
                    ->first();

                if (! $section) {
                    $code = $this->suggestCode($deptId, $canonicalName);
                    $section = Section::create([
                        'department_id' => $deptId,
                        'name' => $canonicalName,
                        'code' => $code,
                        'active' => true,
                        'code_confirmed' => false,
                    ]);
                    $created[] = [
                        'id' => $section->id,
                        'department_id' => $deptId,
                        'name' => $section->name,
                        'code' => $section->code,
                    ];
                }

                $updated = Asset::withTrashed()
                    ->where('department_id', $deptId)
                    ->whereNull('section_id')
                    ->where(function ($query) use ($variants) {
                        foreach ($variants as $v) {
                            $query->orWhereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($v))]);
                        }
                    })
                    ->update([
                        'section_id' => $section->id,
                        'section' => $section->name,
                    ]);

                $linkedCount += $updated;
            }
        });

        return [
            'created' => $created,
            'linked_assets_count' => $linkedCount,
        ];
    }

    /**
     * Suggest a clean 2 to 6 character uppercase alphanumeric code unique within a department.
     */
    public function suggestCode(int $departmentId, string $name): string
    {
        $cleanLetters = preg_replace('/[^A-Za-z0-9\s]/', '', $name);
        $words = array_values(array_filter(explode(' ', trim((string) $cleanLetters))));

        $code = '';
        if (count($words) >= 2 && ctype_digit(end($words))) {
            $digits = array_pop($words);
            if (count($words) === 1 && strlen($words[0]) <= 4) {
                $code = strtoupper($words[0]) . $digits;
            } else {
                $initials = '';
                foreach ($words as $w) {
                    $initials .= strtoupper(substr($w, 0, 1));
                }
                $code = $initials . $digits;
            }
        } elseif (count($words) >= 2) {
            foreach ($words as $w) {
                $code .= strtoupper(substr($w, 0, 1));
            }
        } elseif (count($words) === 1) {
            $code = strtoupper(substr($words[0], 0, 5));
        }

        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
        if (strlen($code) < 2) {
            $code = str_pad($code, 2, 'X');
        }
        if (strlen($code) > 6) {
            $code = substr($code, 0, 6);
        }

        $baseCode = $code;
        $counter = 2;
        while (Section::where('department_id', $departmentId)->where('code', $code)->exists()) {
            $suffix = (string) $counter;
            $allowedLength = 6 - strlen($suffix);
            $code = substr($baseCode, 0, max(1, $allowedLength)) . $suffix;
            $counter++;
        }

        return $code;
    }
}
