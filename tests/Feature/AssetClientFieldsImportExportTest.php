<?php

use App\Exports\AssetsExport;
use App\Exports\AssetsTemplateExport;
use App\Exports\DepreciationExport;
use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use App\Services\AssetImportService;
use Spatie\Activitylog\Models\Activity;

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

    Setting::set('hospital_name', 'Bethel Baptist Hospital, Inc.');
    Setting::set('asset_no_prefix', 'BBH');
});

test('import with plain year stores january 1 of that year', function () {
    $service = app(AssetImportService::class);

    $rows = [
        [
            'asset_no' => 'BBH-YEAR-001',
            'asset_name' => 'Ultrasound Scanner',
            'category' => 'Medical Equipment',
            'status' => 'In Use',
            'asset_condition' => 'In Good Condition',
            'date_acquired' => '2023-01-01',
            'is_valid' => true,
            'is_duplicate' => false,
        ],
    ];

    $result = $service->commit($rows, $this->admin);
    expect($result['imported'])->toBe(1);

    $asset = Asset::where('asset_no', 'BBH-YEAR-001')->first();
    expect($asset)->not->toBeNull();
    expect($asset->date_acquired->format('Y-m-d'))->toBe('2023-01-01');
    expect($asset->date_acquired->format('Y'))->toBe('2023');
});

test('import with full date keeps the complete date', function () {
    $service = app(AssetImportService::class);

    $rows = [
        [
            'asset_no' => 'BBH-DATE-001',
            'asset_name' => 'Centrifuge Machine',
            'category' => 'Laboratory',
            'date_acquired' => '2022-08-25',
            'status' => 'In Use',
            'asset_condition' => 'Brand-new',
            'is_valid' => true,
            'is_duplicate' => false,
        ],
    ];

    $result = $service->commit($rows, $this->admin);
    expect($result['imported'])->toBe(1);

    $asset = Asset::where('asset_no', 'BBH-DATE-001')->first();
    expect($asset)->not->toBeNull();
    expect($asset->date_acquired->format('Y-m-d'))->toBe('2022-08-25');
});

test('import with old headers still imports correctly', function () {
    $service = app(AssetImportService::class);

    $rows = [
        [
            'asset_no' => 'BBH-OLD-001',
            'asset_name' => 'Defibrillator',
            'category' => 'Biomedical',
            'model' => 'LifePak 15',
            'serial_no' => 'SN-9992',
            'date_acquired' => '2021-04-10',
            'department' => 'Emergency Room',
            'location' => 'Bay 2',
            'status' => 'In Use',
            'notes' => 'Legacy equipment tag',
            'is_valid' => true,
            'is_duplicate' => false,
        ],
    ];

    $result = $service->commit($rows, $this->admin);
    expect($result['imported'])->toBe(1);

    $asset = Asset::where('asset_no', 'BBH-OLD-001')->first();
    expect($asset)->not->toBeNull();
    expect($asset->asset_name)->toBe('Defibrillator');
    expect($asset->model)->toBe('LifePak 15');
    expect($asset->department)->toBe('Emergency Room');
    expect($asset->asset_condition)->toBeNull();
});

test('import condition normalization maps near-matches and flags unknowns', function () {
    $service = app(AssetImportService::class);

    // Exact match case-insensitive
    $c1 = $service->normalizeCondition('brand-new');
    expect($c1['value'])->toBe('Brand-new');
    expect($c1['is_unknown'])->toBeFalse();

    // Near-match: Brand new -> Brand-new
    $c2 = $service->normalizeCondition('Brand new');
    expect($c2['value'])->toBe('Brand-new');
    expect($c2['mapped'])->toBeTrue();
    expect($c2['note'])->toContain('will be saved as Brand-new');

    // Near-match: Refurbished -> Refurbish
    $c3 = $service->normalizeCondition('Refurbished');
    expect($c3['value'])->toBe('Refurbish');
    expect($c3['mapped'])->toBeTrue();
    expect($c3['note'])->toContain('will be saved as Refurbish');

    // Near-match: Damaged -> Damage
    $c4 = $service->normalizeCondition('Damaged');
    expect($c4['value'])->toBe('Damage');
    expect($c4['mapped'])->toBeTrue();
    expect($c4['note'])->toContain('will be saved as Damage');

    // Near-match: Good Condition -> In Good Condition
    $c5 = $service->normalizeCondition('Good Condition');
    expect($c5['value'])->toBe('In Good Condition');
    expect($c5['mapped'])->toBeTrue();
    expect($c5['note'])->toContain('will be saved as In Good Condition');

    // Blank -> null
    $c6 = $service->normalizeCondition('');
    expect($c6['value'])->toBeNull();
    expect($c6['is_unknown'])->toBeFalse();

    // Unknown condition
    $c7 = $service->normalizeCondition('Completely Burned Out');
    expect($c7['value'])->toBeNull();
    expect($c7['is_unknown'])->toBeTrue();
    expect($c7['note'])->toContain('Unknown condition');
});

test('import commit persists all new client fields', function () {
    $service = app(AssetImportService::class);

    $rows = [
        [
            'asset_no' => 'BBH-NEW-777',
            'asset_name' => 'Infusion Pump',
            'brand' => 'Baxter',
            'model' => 'Sigma Spectrum',
            'serial_no' => 'PUMP-5544',
            'department' => 'Pediatrics',
            'section' => 'NICU Station 1',
            'location' => 'Room 204',
            'assigned_personnel' => 'Dr. Reyes',
            'category' => 'Medical Equipment',
            'date_acquired' => '2024-01-01',
            'status' => 'In Use',
            'asset_condition' => 'Brand-new',
            'notes' => 'Donated by alumni foundation',
            'is_valid' => true,
            'is_duplicate' => false,
        ],
    ];

    $result = $service->commit($rows, $this->admin);
    expect($result['imported'])->toBe(1);

    $asset = Asset::where('asset_no', 'BBH-NEW-777')->first();
    expect($asset)->not->toBeNull();
    expect($asset->brand)->toBe('Baxter');
    expect($asset->model)->toBe('Sigma Spectrum');
    expect($asset->section)->toBe('NICU Station 1');
    expect($asset->assigned_personnel)->toBe('Dr. Reyes');
    expect($asset->asset_condition)->toBe('Brand-new');
});

test('admin export includes all fields with year bought as year and financials', function () {
    Asset::create([
        'asset_no' => 'BBH-EXP-001',
        'asset_name' => 'Anesthesia Machine',
        'brand' => 'Datex-Ohmeda',
        'model' => 'Aestiva 5',
        'serial_no' => 'AN-1002',
        'department' => 'Surgery',
        'section' => 'OR Suite 1',
        'location' => 'Main OR',
        'assigned_personnel' => 'Nurse Clara',
        'date_acquired' => '2023-05-20',
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'cost' => 500000,
        'salvage_value' => 50000,
        'useful_life_years' => 10,
        'notes' => 'Regular biomedical maintenance check',
    ]);

    $export = new AssetsExport(null, null, $this->admin);
    $headings = $export->headings();
    $rows = $export->array();

    expect($headings)->toContain('asset_description');
    expect($headings)->toContain('brand');
    expect($headings)->toContain('section');
    expect($headings)->toContain('assigned_personnel');
    expect($headings)->toContain('year_bought');
    expect($headings)->toContain('asset_condition');
    expect($headings)->toContain('cost');
    expect($headings)->toContain('current_value');

    $row = $rows[0];
    expect($row[10])->toBe('2023');
    expect($row[11])->toBe('In Good Condition');
    expect($row[13])->toContain('500,000.00');
});

test('staff export contains no cost or value', function () {
    Asset::create([
        'asset_no' => 'BBH-EXP-002',
        'asset_name' => 'Operating Table',
        'brand' => 'Steris',
        'date_acquired' => '2022-01-01',
        'status' => 'In Use',
        'cost' => 850000,
        'salvage_value' => 50000,
        'useful_life_years' => 15,
    ]);

    $export = new AssetsExport(null, null, $this->staff);
    $headings = $export->headings();
    $rows = $export->array();

    expect($headings)->not->toContain('cost');
    expect($headings)->not->toContain('salvage_value');
    expect($headings)->not->toContain('useful_life_years');
    expect($headings)->not->toContain('accumulated_depreciation');
    expect($headings)->not->toContain('current_value');

    $row = $rows[0];
    foreach ($row as $cell) {
        expect((string) $cell)->not->toContain('850000');
        expect((string) $cell)->not->toContain('850,000');
        expect((string) $cell)->not->toContain('₱');
    }
});

test('staff quick search and find contain no cost or value', function () {
    Asset::create([
        'asset_no' => 'BBH-SEC-999',
        'asset_name' => 'High Cost Scanner',
        'brand' => 'Siemens',
        'status' => 'In Use',
        'cost' => 1250000,
        'salvage_value' => 100000,
        'useful_life_years' => 10,
    ]);

    $response = $this->actingAs($this->staff)->getJson(route('assets.quick-search', ['q' => 'Siemens']));
    $response->assertOk();
    $content = $response->getContent();
    expect($content)->not->toContain('1250000');
    expect($content)->not->toContain('1,250,000');
    expect($content)->not->toContain('cost');

    $findResponse = $this->actingAs($this->staff)->get(route('assets.find', ['q' => 'BBH-SEC-999']));
    $findResponse->assertOk();
    $findContent = $findResponse->getContent();
    expect($findContent)->not->toContain('1,250,000');
});

test('activity log records changes to client fields', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-AUDIT-1',
        'asset_name' => 'Patient Stretcher',
        'brand' => 'OldBrand',
        'section' => 'OldSection',
        'assigned_personnel' => 'OldNurse',
        'asset_condition' => 'Damage',
        'status' => 'In Use',
    ]);

    $asset->update([
        'brand' => 'Hill-Rom',
        'section' => 'ICU Bay 4',
        'assigned_personnel' => 'Nurse Joy',
        'asset_condition' => 'Refurbish',
    ]);

    $latestActivity = Activity::forSubject($asset)
        ->where('event', 'updated')
        ->latest('id')
        ->first();
    expect($latestActivity)->not->toBeNull();
    $properties = $latestActivity->properties;
    expect($properties)->toHaveKey('attributes');
    expect($properties['attributes']['brand'])->toBe('Hill-Rom');
    expect($properties['attributes']['section'])->toBe('ICU Bay 4');
    expect($properties['attributes']['assigned_personnel'])->toBe('Nurse Joy');
    expect($properties['attributes']['asset_condition'])->toBe('Refurbish');
});

test('assets template export includes all client fields', function () {
    $template = new AssetsTemplateExport();
    $headings = $template->headings();
    $rows = $template->array();

    expect($headings)->toContain('asset_description');
    expect($headings)->toContain('brand');
    expect($headings)->toContain('model');
    expect($headings)->toContain('serial_number');
    expect($headings)->toContain('department');
    expect($headings)->toContain('section');
    expect($headings)->toContain('assigned_personnel');
    expect($headings)->toContain('category');
    expect($headings)->toContain('year_bought');
    expect($headings)->toContain('asset_condition');
    expect($headings)->toContain('status');
    expect($headings)->toContain('remarks');

    expect($rows)->toHaveCount(2);
    expect($rows[0][1])->toBe('Patient Monitor');
    expect($rows[0][2])->toBe('Mindray');
    expect($rows[0][9])->toBe('2023');
    expect($rows[0][10])->toBe('In Good Condition');
});
