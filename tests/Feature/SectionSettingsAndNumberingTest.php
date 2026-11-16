<?php

use App\Models\Asset;
use App\Models\Department;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Services\AssetNumberService;
use App\Services\SectionBackfillService;
use Database\Seeders\DepartmentSectionSeeder;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Staff Nurse',
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('asset_no_prefix', 'BBH');
    Setting::set('asset_number_digits', '4');
    Setting::set('asset_number_scope', 'department');
    Setting::set('reassign_number_on_dept_change', '1');
    Setting::set('include_section_in_asset_no', '1');

    $this->surg = Department::create([
        'name' => 'Surgery',
        'code' => 'SURG',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    $this->icu = Department::create([
        'name' => 'Intensive Care Unit',
        'code' => 'ICU',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);
});

test('admin can create, update, and confirm sections per department in settings while staff is forbidden', function () {
    $this->actingAs($this->staff)
        ->post(route('settings.sections.store'), [
            'department_id' => $this->surg->id,
            'name' => 'Trauma Bay 1',
            'code' => 'TB1',
        ])
        ->assertForbidden();

    $createRes = $this->actingAs($this->admin)
        ->post(route('settings.sections.store'), [
            'department_id' => $this->surg->id,
            'name' => 'Trauma Bay 1',
            'code' => 'tb1',
            'active' => '1',
        ]);

    $createRes->assertRedirect(route('settings.index', ['tab' => 'departments']));

    $section = Section::where('department_id', $this->surg->id)->where('name', 'Trauma Bay 1')->first();
    expect($section)->not->toBeNull()
        ->and($section->code)->toBe('TB1')
        ->and($section->code_confirmed)->toBeTrue()
        ->and($section->active)->toBeTrue();

    // Duplicate code inside the same department is rejected
    $this->actingAs($this->admin)
        ->post(route('settings.sections.store'), [
            'department_id' => $this->surg->id,
            'name' => 'Trauma Bay One',
            'code' => 'TB1',
        ])
        ->assertSessionHasErrors(['code']);

    // Same code in a different department is allowed
    $this->actingAs($this->admin)
        ->post(route('settings.sections.store'), [
            'department_id' => $this->icu->id,
            'name' => 'Bay 1',
            'code' => 'TB1',
            'active' => '1',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'departments']));

    // Rename section updates linked assets' section string
    $asset = Asset::create([
        'asset_no' => 'BBHSURG-TB1-0001',
        'asset_name' => 'Monitor',
        'department_id' => $this->surg->id,
        'department' => $this->surg->name,
        'section_id' => $section->id,
        'section' => 'Trauma Bay 1',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->admin)
        ->put(route('settings.sections.update', $section), [
            'department_id' => $this->surg->id,
            'name' => 'Trauma Bay 1A',
            'code' => 'TB1A',
            'active' => '1',
            'code_confirmed' => '1',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'departments']));

    expect($section->fresh()->code)->toBe('TB1A')
        ->and($asset->fresh()->section)->toBe('Trauma Bay 1A');

    // Confirm code endpoint works
    $unconfirmed = Section::create([
        'department_id' => $this->icu->id,
        'name' => 'ICU 2',
        'code' => 'ICU2',
        'active' => true,
        'code_confirmed' => false,
    ]);

    $this->actingAs($this->staff)
        ->post(route('settings.sections.confirm-code', $unconfirmed))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->post(route('settings.sections.confirm-code', $unconfirmed))
        ->assertRedirect(route('settings.index', ['tab' => 'departments']));

    expect($unconfirmed->fresh()->code_confirmed)->toBeTrue();
});

test('asset numbering uses section code when setting is enabled and omits it when disabled', function () {
    $tb1 = Section::create([
        'department_id' => $this->surg->id,
        'name' => 'Trauma Bay 1',
        'code' => 'TB1',
        'active' => true,
        'code_confirmed' => true,
    ]);

    $previewRes = $this->actingAs($this->admin)
        ->getJson(route('departments.preview-next-number', ['department' => $this->surg->id, 'section_id' => $tb1->id]));

    $previewRes->assertOk()->assertJson([
        'next_number' => 'BBHSURG-TB1-0001',
        'code_confirmed' => true,
    ]);
    expect($this->surg->fresh()->next_number)->toBe(1);

    $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'Defibrillator',
        'department_id' => $this->surg->id,
        'section_id' => $tb1->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ])->assertRedirect();

    $asset1 = Asset::where('asset_name', 'Defibrillator')->first();
    expect($asset1->asset_no)->toBe('BBHSURG-TB1-0001')
        ->and($asset1->section_id)->toBe($tb1->id)
        ->and($asset1->section)->toBe('Trauma Bay 1');

    expect(app(AssetNumberService::class)->matchesPattern('BBHSURG-TB1-0001', $this->surg))->toBeTrue();

    // Turn off include_section_in_asset_no in settings
    $this->actingAs($this->admin)->post(route('settings.numbering.update'), [
        'asset_no_prefix' => 'BBH',
        'asset_number_digits' => 4,
        'asset_number_scope' => 'department',
        'reassign_number_on_dept_change' => '1',
    ])->assertRedirect(route('settings.index', ['tab' => 'departments']));

    expect(Setting::get('include_section_in_asset_no'))->toBe('0');

    $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'Infusion Pump',
        'department_id' => $this->surg->id,
        'section_id' => $tb1->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ])->assertRedirect();

    $asset2 = Asset::where('asset_name', 'Infusion Pump')->first();
    expect($asset2->asset_no)->toBe('BBHSURG-0002');
});

test('create and edit forms require department_id and matching section_id and reject unconfirmed or mismatched sections', function () {
    $tb1 = Section::create([
        'department_id' => $this->surg->id,
        'name' => 'Trauma Bay 1',
        'code' => 'TB1',
        'active' => true,
        'code_confirmed' => true,
    ]);

    $icu2 = Section::create([
        'department_id' => $this->icu->id,
        'name' => 'ICU 2',
        'code' => 'ICU2',
        'active' => true,
        'code_confirmed' => false,
    ]);

    $createHtml = $this->actingAs($this->admin)->get(route('assets.create'));
    $createHtml->assertOk();
    expect(preg_match('/<select[^>]*id="department_id"[^>]*\brequired\b/s', $createHtml->getContent()))->toBe(1);
    expect(preg_match('/<select[^>]*id="section_id"[^>]*\brequired\b/s', $createHtml->getContent()))->toBe(1);

    // Empty department_id and section_id fail validation
    $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'ECG Machine',
        'department_id' => '',
        'section_id' => '',
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ])->assertSessionHasErrors(['department_id', 'section_id']);

    // Mismatched section_id (ICU section submitted under Surgery department) fails validation
    $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'ECG Machine',
        'department_id' => $this->surg->id,
        'section_id' => $icu2->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ])->assertSessionHasErrors(['section_id']);

    // Unconfirmed section blocks automated numbering
    $this->actingAs($this->admin)->post(route('assets.store'), [
        'asset_name' => 'ECG Machine',
        'department_id' => $this->icu->id,
        'section_id' => $icu2->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ])->assertSessionHasErrors(['section_id']);

    $asset = Asset::create([
        'asset_no' => 'BBHSURG-TB1-0001',
        'asset_name' => 'Monitor',
        'department_id' => $this->surg->id,
        'department' => $this->surg->name,
        'section_id' => $tb1->id,
        'section' => $tb1->name,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ]);

    $editHtml = $this->actingAs($this->admin)->get(route('assets.edit', $asset));
    $editHtml->assertOk();
    expect(preg_match('/<select[^>]*id="department_id"[^>]*\brequired\b/s', $editHtml->getContent()))->toBe(1);
    expect(preg_match('/<select[^>]*id="section_id"[^>]*\brequired\b/s', $editHtml->getContent()))->toBe(1);
});

test('section backfill creates standardized unconfirmed section codes and links existing assets', function () {
    $nd = Department::create([
        'name' => 'Nursing Department',
        'code' => 'ND',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    $a1 = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Refrigerator',
        'department_id' => $nd->id,
        'department' => $nd->name,
        'section' => 'Station 3',
        'status' => 'In Use',
    ]);

    $a2 = Asset::create([
        'asset_no' => 'BBHSURG-0001',
        'asset_name' => 'Stretcher',
        'department_id' => $this->surg->id,
        'department' => $this->surg->name,
        'section' => 'Trauma Bay 1',
        'status' => 'In Use',
    ]);

    $result = app(SectionBackfillService::class)->runBackfill();

    expect($result['linked_assets_count'])->toBe(2);

    $s3 = Section::where('department_id', $nd->id)->where('name', 'Station 3')->first();
    $tb1 = Section::where('department_id', $this->surg->id)->where('name', 'Trauma Bay 1')->first();

    expect($s3)->not->toBeNull()
        ->and($s3->code)->toBe('S3')
        ->and($s3->code_confirmed)->toBeFalse()
        ->and($a1->fresh()->section_id)->toBe($s3->id);

    expect($tb1)->not->toBeNull()
        ->and($tb1->code)->toBe('TB1')
        ->and($tb1->code_confirmed)->toBeFalse()
        ->and($a2->fresh()->section_id)->toBe($tb1->id);
});

test('admin can remove unused department and section while in-use records are locked and staff is forbidden', function () {
    $unusedSec = Section::create([
        'department_id' => $this->surg->id,
        'name' => 'Recovery Room',
        'code' => 'RR',
        'active' => true,
        'code_confirmed' => true,
    ]);

    $usedSec = Section::create([
        'department_id' => $this->surg->id,
        'name' => 'Trauma Bay 1',
        'code' => 'TB1',
        'active' => true,
        'code_confirmed' => true,
    ]);

    $unusedDept = Department::create([
        'name' => 'Pharmacy',
        'code' => 'PHARM',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);

    Asset::create([
        'asset_no' => 'BBHSURG-TB1-0001',
        'asset_name' => 'Monitor',
        'department_id' => $this->surg->id,
        'department' => $this->surg->name,
        'section_id' => $usedSec->id,
        'section' => $usedSec->name,
        'status' => 'In Use',
    ]);

    // Staff cannot remove departments or sections
    $this->actingAs($this->staff)
        ->delete(route('settings.sections.destroy', $unusedSec))
        ->assertForbidden();
    $this->actingAs($this->staff)
        ->delete(route('settings.departments.destroy', $unusedDept))
        ->assertForbidden();

    // Settings UI shows Remove for unused records and Locked for in-use records
    $settingsHtml = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'departments']));
    $settingsHtml->assertOk()
        ->assertSee('Remove')
        ->assertSee('Locked');

    // In-use department and section cannot be deleted
    $this->actingAs($this->admin)
        ->delete(route('settings.sections.destroy', $usedSec))
        ->assertSessionHasErrors(['section']);
    expect(Section::find($usedSec->id))->not->toBeNull();

    $this->actingAs($this->admin)
        ->delete(route('settings.departments.destroy', $this->surg))
        ->assertSessionHasErrors(['department']);
    expect(Department::find($this->surg->id))->not->toBeNull();

    // Unused section and unused department can be deleted
    $this->actingAs($this->admin)
        ->delete(route('settings.sections.destroy', $unusedSec))
        ->assertRedirect(route('settings.index', ['tab' => 'departments']));
    expect(Section::find($unusedSec->id))->toBeNull();

    $this->actingAs($this->admin)
        ->delete(route('settings.departments.destroy', $unusedDept))
        ->assertRedirect(route('settings.index', ['tab' => 'departments']));
    expect(Department::find($unusedDept->id))->toBeNull();
});

test('DepartmentSectionSeeder populates all 10 hospital departments and 38 sections idempotently without resetting counters', function () {
    $this->surg->update(['next_number' => 5]);

    $this->seed(DepartmentSectionSeeder::class);
    $this->seed(DepartmentSectionSeeder::class);

    expect(Department::count())->toBe(10)
        ->and(Section::count())->toBe(38);

    $this->surg->refresh();
    expect($this->surg->name)->toBe('Surgery / Operating Room')
        ->and($this->surg->next_number)->toBe(5)
        ->and($this->surg->code_confirmed)->toBeTrue();

    foreach (Department::all() as $department) {
        expect($department->active)->toBeTrue()
            ->and($department->code_confirmed)->toBeTrue()
            ->and(strlen($department->code))->toBeGreaterThanOrEqual(2)
            ->and(strlen($department->code))->toBeLessThanOrEqual(6);
    }

    foreach (Section::all() as $section) {
        expect($section->active)->toBeTrue()
            ->and($section->code_confirmed)->toBeTrue()
            ->and(strlen($section->code))->toBeGreaterThanOrEqual(2)
            ->and(strlen($section->code))->toBeLessThanOrEqual(6);
    }

    // Verify expected sample asset numbers when next_number = 1
    $this->surg->update(['next_number' => 1]);
    $numberService = app(AssetNumberService::class);

    $samples = [
        ['ND', 'S3', 'BBHND-S3-0001'],
        ['ER', 'TB1', 'BBHER-TB1-0001'],
        ['ICU', 'ICU2', 'BBHICU-ICU2-0001'],
        ['SURG', 'OR1', 'BBHSURG-OR1-0001'],
        ['OPD', 'YAKAP', 'BBHOPD-YAKAP-0001'],
        ['LAB', 'HEMA', 'BBHLAB-HEMA-0001'],
        ['RAD', 'CT', 'BBHRAD-CT-0001'],
        ['PHARM', 'GAMOT', 'BBHPHARM-GAMOT-0001'],
        ['ADMIN', 'CSH', 'BBHADMIN-CSH-0001'],
        ['IT', 'NET', 'BBHIT-NET-0001'],
    ];

    foreach ($samples as [$deptCode, $secCode, $expected]) {
        $dept = Department::where('code', $deptCode)->firstOrFail();
        $section = Section::where('department_id', $dept->id)->where('code', $secCode)->firstOrFail();
        expect($numberService->previewNextNumber($dept, $section))->toBe($expected);
    }

    // Asset create page includes IT Department and its sections in dropdown markup
    $this->actingAs($this->admin)
        ->get(route('assets.create'))
        ->assertOk()
        ->assertSee('Information Technology Department (IT)')
        ->assertSee('IT Office (ITO)')
        ->assertSee('Server &amp; Network (NET)', false)
        ->assertSee('Technical Support (SUP)')
        ->assertSee('Hospital Information System (HIS)');
});

test('DesktopBootstrapService adds IT department on v1-seeded databases without resurrecting deleted departments', function () {
    Setting::set('hospital_departments_seeded', '1');
    Setting::set('hospital_departments_seed_version', null);

    app(\App\Services\DesktopBootstrapService::class)->boot();

    expect(Department::where('code', 'IT')->exists())->toBeTrue()
        ->and(Department::where('code', 'ND')->exists())->toBeFalse()
        ->and(Setting::get('hospital_departments_seed_version'))->toBe('2');
});
