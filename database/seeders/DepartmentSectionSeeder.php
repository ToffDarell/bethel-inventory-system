<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Seeder;

class DepartmentSectionSeeder extends Seeder
{
    /**
     * Standard Bethel Baptist Hospital departments and sections with short property tag codes.
     *
     * @return array<int, array{name: string, code: string, sections: array<int, array{name: string, code: string}>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Nursing Department',
                'code' => 'ND',
                'sections' => [
                    ['name' => 'Station 1', 'code' => 'S1'],
                    ['name' => 'Station 2', 'code' => 'S2'],
                    ['name' => 'Station 3', 'code' => 'S3'],
                    ['name' => 'Nelson Wing', 'code' => 'NW'],
                ],
            ],
            [
                'name' => 'Emergency Room',
                'code' => 'ER',
                'sections' => [
                    ['name' => 'Triage', 'code' => 'TRI'],
                    ['name' => 'Trauma Bay 1', 'code' => 'TB1'],
                    ['name' => 'ER Observation', 'code' => 'OBS'],
                ],
            ],
            [
                'name' => 'Intensive Care Unit',
                'code' => 'ICU',
                'sections' => [
                    ['name' => 'ICU 1', 'code' => 'ICU1'],
                    ['name' => 'ICU 2', 'code' => 'ICU2'],
                    ['name' => 'NICU', 'code' => 'NICU'],
                ],
            ],
            [
                'name' => 'Surgery / Operating Room',
                'code' => 'SURG',
                'sections' => [
                    ['name' => 'Operating Room 1', 'code' => 'OR1'],
                    ['name' => 'Recovery Room', 'code' => 'RR'],
                    ['name' => 'CSSD', 'code' => 'CSSD'],
                ],
            ],
            [
                'name' => 'Outpatient Department',
                'code' => 'OPD',
                'sections' => [
                    ['name' => 'General / Family Med', 'code' => 'GEN'],
                    ['name' => 'Pediatrics', 'code' => 'PED'],
                    ['name' => 'OB-GYN', 'code' => 'OB'],
                    ['name' => 'YAKAP Clinic', 'code' => 'YAKAP'],
                    ['name' => 'Occupational Med', 'code' => 'OCC'],
                ],
            ],
            [
                'name' => 'Laboratory',
                'code' => 'LAB',
                'sections' => [
                    ['name' => 'Clinical Chemistry', 'code' => 'CHEM'],
                    ['name' => 'Hematology', 'code' => 'HEMA'],
                    ['name' => 'Microscopy', 'code' => 'MIC'],
                    ['name' => 'Pathology', 'code' => 'PATH'],
                    ['name' => 'Newborn Screening', 'code' => 'NBS'],
                ],
            ],
            [
                'name' => 'Radiology & Diagnostics',
                'code' => 'RAD',
                'sections' => [
                    ['name' => 'X-Ray', 'code' => 'XRAY'],
                    ['name' => 'Ultrasound', 'code' => 'UTZ'],
                    ['name' => '2D-Echo', 'code' => 'ECHO'],
                    ['name' => 'CT Scan', 'code' => 'CT'],
                    ['name' => 'ECG', 'code' => 'ECG'],
                ],
            ],
            [
                'name' => 'Pharmacy',
                'code' => 'PHARM',
                'sections' => [
                    ['name' => 'Main Dispensing', 'code' => 'MAIN'],
                    ['name' => 'PhilHealth GAMOT', 'code' => 'GAMOT'],
                ],
            ],
            [
                'name' => 'Administration & Support',
                'code' => 'ADMIN',
                'sections' => [
                    ['name' => 'Admin Office', 'code' => 'ADM'],
                    ['name' => 'Cashier / Billing', 'code' => 'CSH'],
                    ['name' => 'Chaplaincy', 'code' => 'CHAP'],
                    ['name' => 'Maintenance / Biomed', 'code' => 'BMD'],
                ],
            ],
            [
                'name' => 'Information Technology Department',
                'code' => 'IT',
                'sections' => [
                    ['name' => 'IT Office', 'code' => 'ITO'],
                    ['name' => 'Server & Network', 'code' => 'NET'],
                    ['name' => 'Technical Support', 'code' => 'SUP'],
                    ['name' => 'Hospital Information System', 'code' => 'HIS'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::definitions() as $deptData) {
            $this->seedDefinition($deptData);
        }
    }

    /**
     * Seed a single standard department definition (and its sections) by code.
     */
    public function seedByCode(string $code): void
    {
        $target = strtoupper(trim($code));

        foreach (self::definitions() as $deptData) {
            if (strtoupper($deptData['code']) === $target) {
                $this->seedDefinition($deptData);
            }
        }
    }

    /**
     * Create or update a single department and its standard sections without disturbing counters.
     *
     * @param  array{name: string, code: string, sections: array<int, array{name: string, code: string}>}  $deptData
     */
    protected function seedDefinition(array $deptData): void
    {
            $department = Department::whereRaw('UPPER(code) = ?', [strtoupper($deptData['code'])])
                ->first();

            if (!$department) {
                $department = Department::whereRaw('LOWER(name) = ?', [strtolower($deptData['name'])])
                    ->first();
            }

            if ($department) {
                $oldName = $department->name;
                $updates = [
                    'active' => true,
                    'code_confirmed' => true,
                ];

                $nameConflict = Department::where('id', '!=', $department->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($deptData['name'])])
                    ->exists();
                if (!$nameConflict) {
                    $updates['name'] = $deptData['name'];
                }

                $codeConflict = Department::where('id', '!=', $department->id)
                    ->whereRaw('UPPER(code) = ?', [strtoupper($deptData['code'])])
                    ->exists();
                if (!$codeConflict) {
                    $updates['code'] = strtoupper($deptData['code']);
                }

                $department->update($updates);

                if (isset($updates['name']) && $updates['name'] !== $oldName) {
                    Asset::where('department_id', $department->id)
                        ->update(['department' => $updates['name']]);
                }
            } else {
                $department = Department::create([
                    'name' => $deptData['name'],
                    'code' => strtoupper($deptData['code']),
                    'next_number' => 1,
                    'active' => true,
                    'code_confirmed' => true,
                ]);
            }

            foreach ($deptData['sections'] as $sectionData) {
                $section = Section::where('department_id', $department->id)
                    ->whereRaw('UPPER(code) = ?', [strtoupper($sectionData['code'])])
                    ->first();

                if (!$section) {
                    $section = Section::where('department_id', $department->id)
                        ->whereRaw('LOWER(name) = ?', [strtolower($sectionData['name'])])
                        ->first();
                }

                if ($section) {
                    $oldSectionName = $section->name;
                    $secUpdates = [
                        'active' => true,
                        'code_confirmed' => true,
                    ];

                    $secNameConflict = Section::where('department_id', $department->id)
                        ->where('id', '!=', $section->id)
                        ->whereRaw('LOWER(name) = ?', [strtolower($sectionData['name'])])
                        ->exists();
                    if (!$secNameConflict) {
                        $secUpdates['name'] = $sectionData['name'];
                    }

                    $secCodeConflict = Section::where('department_id', $department->id)
                        ->where('id', '!=', $section->id)
                        ->whereRaw('UPPER(code) = ?', [strtoupper($sectionData['code'])])
                        ->exists();
                    if (!$secCodeConflict) {
                        $secUpdates['code'] = strtoupper($sectionData['code']);
                    }

                    $section->update($secUpdates);

                    if (isset($secUpdates['name']) && $secUpdates['name'] !== $oldSectionName) {
                        Asset::where('section_id', $section->id)
                            ->update(['section' => $secUpdates['name']]);
                    }
                } else {
                    Section::create([
                        'department_id' => $department->id,
                        'name' => $sectionData['name'],
                        'code' => strtoupper($sectionData['code']),
                        'active' => true,
                        'code_confirmed' => true,
                    ]);
                }
            }
    }
}
