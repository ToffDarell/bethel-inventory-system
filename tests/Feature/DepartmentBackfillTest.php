<?php

use App\Models\Asset;
use App\Models\Department;
use App\Services\DepartmentBackfillService;

test('backfill normalizes whitespace and case, merges variants, and suggests 2-6 char codes', function () {
    // Set up assets with varying cases and whitespaces
    Asset::create([
        'asset_no' => 'OLD-001',
        'asset_name' => 'Monitor A',
        'department' => 'ICU',
        'status' => 'In Use',
    ]);
    Asset::create([
        'asset_no' => 'OLD-002',
        'asset_name' => 'Monitor B',
        'department' => '  icu  ',
        'status' => 'In Use',
    ]);
    Asset::create([
        'asset_no' => 'OLD-003',
        'asset_name' => 'ECG Unit',
        'department' => 'Emergency Room',
        'status' => 'In Use',
    ]);
    Asset::create([
        'asset_no' => 'OLD-004',
        'asset_name' => 'Centrifuge',
        'department' => 'Laboratory',
        'status' => 'In Use',
    ]);

    $service = new DepartmentBackfillService();
    $report = $service->runBackfill();

    expect($report['linked_assets_count'])->toBe(4);
    expect(Department::count())->toBe(3); // ICU (merged), Emergency Room, Laboratory

    $icu = Department::where('name', 'ICU')->first();
    expect($icu)->not->toBeNull();
    expect($icu->code_confirmed)->toBeFalse(); // Needs review
    expect(strlen($icu->code))->toBeGreaterThanOrEqual(2);
    expect(strlen($icu->code))->toBeLessThanOrEqual(6);
    expect($icu->code)->toBe(strtoupper($icu->code));

    // Check merge list recorded
    expect($report['merge_list'])->toHaveKey('ICU');
    expect($report['merge_list']['ICU'])->toContain('ICU', 'icu');

    // Both assets with ICU should point to the merged department
    $asset1 = Asset::where('asset_no', 'OLD-001')->first();
    $asset2 = Asset::where('asset_no', 'OLD-002')->first();
    expect($asset1->department_id)->toBe($icu->id);
    expect($asset2->department_id)->toBe($icu->id);
    expect($asset1->department)->toBe('ICU');
    expect($asset2->department)->toBe('ICU');

    $er = Department::where('name', 'Emergency Room')->first();
    expect($er)->not->toBeNull();
    expect($er->code_confirmed)->toBeFalse();
    expect($er->code)->toBe('ER');
});
