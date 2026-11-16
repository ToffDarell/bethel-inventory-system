<?php

use App\Exports\AssetsExport;
use App\Exports\AssetsTemplateExport;
use App\Exports\DepreciationExport;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin Officer',
        'username' => 'admin_officer',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Nurse Staff',
        'username' => 'nurse_staff',
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('hospital_name', 'Bethel Baptist Hospital, Inc.');
    Setting::set('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');

    $this->category = Category::create([
        'name' => 'Biomedical Devices',
        'useful_life_years' => 10,
    ]);

    Asset::create([
        'asset_no' => 'BBH-TEST-001',
        'asset_name' => 'Infusion Pump',
        'brand' => 'B. Braun',
        'model' => 'Infusomat',
        'serial_no' => 'SN-INF-991',
        'department' => 'Pediatrics',
        'section' => 'Ward A',
        'location' => 'Bed 3',
        'assigned_personnel' => 'Nurse Jane',
        'category_id' => $this->category->id,
        'date_acquired' => '2022-01-01',
        'asset_condition' => 'In Good Condition',
        'status' => 'In Use',
        'cost' => 60000.00,
        'salvage_value' => 6000.00,
        'useful_life_years' => 10,
        'notes' => 'Regular testing passed',
    ]);

    Asset::create([
        'asset_no' => 'BBH-TEST-002',
        'asset_name' => 'Oxygen Concentrator',
        'brand' => 'Philips',
        'model' => 'EverFlo',
        'serial_no' => 'SN-OXY-882',
        'department' => 'Emergency Room',
        'section' => 'Triage',
        'location' => 'Bay 1',
        'assigned_personnel' => 'Nurse Mark',
        'category_id' => $this->category->id,
        'date_acquired' => '2023-01-01',
        'asset_condition' => 'Brand-new',
        'status' => 'In Use',
        'cost' => null, // No cost entered asset
        'salvage_value' => null,
        'useful_life_years' => null,
        'notes' => 'Donated unit',
    ]);
});

test('depreciation excel export generates publication-grade two-sheet workbook with formulas', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'dep_test_') . '.xlsx';
    $export = new DepreciationExport(null, null, '2024-01-01', 'xlsx', $this->admin);

    $response = Excel::download($export, basename($tempFile));
    $filePath = $response->getFile()->getPathname();

    expect(file_exists($filePath))->toBeTrue();

    $spreadsheet = IOFactory::load($filePath);
    $sheetNames = $spreadsheet->getSheetNames();

    expect($sheetNames)->toContain('Depreciation');
    expect($sheetNames)->toContain('By Category');

    // Check Sheet 1 (Depreciation)
    $sheet1 = $spreadsheet->getSheetByName('Depreciation');
    expect($sheet1->getCell('C1')->getValue())->toBe('Bethel Baptist Hospital, Inc.');
    expect($sheet1->getCell('C3')->getValue())->toBe('DEPRECIATION REPORT');
    expect((string) $sheet1->getCell('A5')->getValue())->toContain('Straight-Line Method');
    expect((string) $sheet1->getCell('A5')->getValue())->toContain('Estimated values for tracking');

    // Check Headers at Row 6
    expect($sheet1->getCell('A6')->getValue())->toBe('Asset No.');
    expect($sheet1->getCell('B6')->getValue())->toBe('Asset Description');
    expect($sheet1->getCell('I6')->getValue())->toBe('Cost');
    expect($sheet1->getCell('K6')->getValue())->toBe('Accumulated Depreciation');
    expect($sheet1->getCell('L6')->getValue())->toBe('Current Value');

    // Check Data Rows
    expect($sheet1->getCell('A7')->getValue())->toBe('BBH-TEST-001');
    expect((float) $sheet1->getCell('I7')->getValue())->toBe(60000.0);
    expect($sheet1->getCell('A8')->getValue())->toBe('BBH-TEST-002');
    expect($sheet1->getCell('I8')->getValue())->toBe('No cost entered');

    // Check Totals Row at Row 9
    expect($sheet1->getCell('A9')->getValue())->toBe('TOTAL ASSET VALUE');
    expect($sheet1->getCell('I9')->getValue())->toBe('=SUM(I7:I8)');
    expect($sheet1->getCell('K9')->getValue())->toBe('=SUM(K7:K8)');
    expect($sheet1->getCell('L9')->getValue())->toBe('=SUM(L7:L8)');

    // Check Note below table
    expect((string) $sheet1->getCell('A10')->getValue())->toContain('No cost entered');

    // Check Sheet 2 (By Category)
    $sheet2 = $spreadsheet->getSheetByName('By Category');
    expect($sheet2->getCell('B1')->getValue())->toBe('Bethel Baptist Hospital, Inc.');
    expect($sheet2->getCell('B2')->getValue())->toBe('DEPRECIATION SUMMARY BY CATEGORY');
    expect($sheet2->getCell('A5')->getValue())->toBe('Category');
    expect($sheet2->getCell('B5')->getValue())->toBe('Asset Count');
    expect($sheet2->getCell('A6')->getValue())->toBe('Biomedical Devices');
    expect((int) $sheet2->getCell('B6')->getValue())->toBe(2);
    expect($sheet2->getCell('A7')->getValue())->toBe('TOTAL');
    expect($sheet2->getCell('B7')->getValue())->toBe('=SUM(B6:B6)');
    expect($sheet2->getCell('C7')->getValue())->toBe('=SUM(C6:C6)');

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    @unlink($filePath);
});

test('assets excel export provides full financial columns and sum formulas for admins', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'asset_adm_') . '.xlsx';
    $export = new AssetsExport(null, null, $this->admin, 'xlsx');

    $response = Excel::download($export, basename($tempFile));
    $filePath = $response->getFile()->getPathname();

    expect(file_exists($filePath))->toBeTrue();

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    expect($sheet->getTitle())->toBe('Asset Inventory');
    expect($sheet->getCell('C1')->getValue())->toBe('Bethel Baptist Hospital, Inc.');
    expect($sheet->getCell('C3')->getValue())->toBe('ASSET INVENTORY REPORT');

    // Header check
    expect($sheet->getCell('A6')->getValue())->toBe('Asset No.');
    expect($sheet->getCell('N6')->getValue())->toBe('Cost');
    expect($sheet->getCell('P6')->getValue())->toBe('Salvage Value');
    expect($sheet->getCell('Q6')->getValue())->toBe('Accumulated Depreciation');
    expect($sheet->getCell('R6')->getValue())->toBe('Current Value');

    // Totals row check
    expect($sheet->getCell('A9')->getValue())->toBe('TOTAL ASSET VALUE');
    expect($sheet->getCell('N9')->getValue())->toBe('=SUM(N7:N8)');
    expect($sheet->getCell('P9')->getValue())->toBe('=SUM(P7:P8)');
    expect($sheet->getCell('Q9')->getValue())->toBe('=SUM(Q7:Q8)');
    expect($sheet->getCell('R9')->getValue())->toBe('=SUM(R7:R8)');

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    @unlink($filePath);
});

test('assets excel export strictly omits all financial columns, formulas, and money values for staff', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'asset_stf_') . '.xlsx';
    $export = new AssetsExport(null, null, $this->staff, 'xlsx');

    $response = Excel::download($export, basename($tempFile));
    $filePath = $response->getFile()->getPathname();

    expect(file_exists($filePath))->toBeTrue();

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    expect($sheet->getTitle())->toBe('Asset Inventory');

    // Check headers at Row 6
    $headers = [];
    foreach (range('A', 'Z') as $col) {
        $val = $sheet->getCell($col . '6')->getValue();
        if ($val !== null && $val !== '') {
            $headers[$col] = $val;
        }
    }

    expect($headers)->toContain('Asset No.');
    expect($headers)->toContain('Asset Description');
    expect($headers)->toContain('Department');
    expect($headers)->toContain('Remarks');
    expect($headers)->not->toContain('Cost');
    expect($headers)->not->toContain('Useful Life (years)');
    expect($headers)->not->toContain('Salvage Value');
    expect($headers)->not->toContain('Accumulated Depreciation');
    expect($headers)->not->toContain('Current Value');

    // Check all data cells for absence of cost or currency symbol
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    for ($r = 6; $r <= $highestRow; $r++) {
        for ($c = 'A'; $c <= $highestCol; $c++) {
            $val = (string) $sheet->getCell($c . $r)->getValue();
            expect($val)->not->toContain('60000');
            expect($val)->not->toContain('60,000');
            expect($val)->not->toContain('₱');
            expect($val)->not->toContain('TOTAL ASSET VALUE');
            expect($val)->not->toContain('=SUM');
        }
    }

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    @unlink($filePath);
});

test('import template excel export has Read Me guidance sheet and clean data sheet', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'tmpl_test_') . '.xlsx';
    $export = new AssetsTemplateExport('xlsx');

    $response = Excel::download($export, basename($tempFile));
    $filePath = $response->getFile()->getPathname();

    expect(file_exists($filePath))->toBeTrue();

    $spreadsheet = IOFactory::load($filePath);
    $sheetNames = $spreadsheet->getSheetNames();

    expect($sheetNames)->toBe(['Read Me', 'Assets Template']);

    // Sheet 1: Read Me
    $readMe = $spreadsheet->getSheetByName('Read Me');
    expect($readMe->getCell('A1')->getValue())->toBe('Bethel Baptist Hospital, Inc.');
    expect($readMe->getCell('A2')->getValue())->toBe('ASSET IMPORT SPREADSHEET GUIDE');
    expect($readMe->getCell('A5')->getValue())->toBe('Column Name');
    expect($readMe->getCell('B5')->getValue())->toBe('Required / Optional');
    expect($readMe->getCell('C5')->getValue())->toBe('Allowed Values / Format');

    // Check allowed conditions documented
    $readMeContent = '';
    for ($r = 6; $r <= 25; $r++) {
        $readMeContent .= $readMe->getCell('A' . $r)->getValue() . ' ' . $readMe->getCell('C' . $r)->getValue() . "\n";
    }
    expect($readMeContent)->toContain('Brand-new');
    expect($readMeContent)->toContain('Refurbish');
    expect($readMeContent)->toContain('Damage');
    expect($readMeContent)->toContain('In Good Condition');

    // Sheet 2: Clean data sheet without logo or instructions header
    $dataSheet = $spreadsheet->getSheetByName('Assets Template');
    expect($dataSheet->getCell('A1')->getValue())->toBe('asset_no');
    expect($dataSheet->getCell('B1')->getValue())->toBe('asset_description');
    expect($dataSheet->getCell('A2')->getValue())->toBe('BBHIND-S300002315');
    expect($dataSheet->getCell('B2')->getValue())->toBe('Patient Monitor');

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    @unlink($filePath);
});

test('raw data csv exports remain plain and single-sheet', function () {
    // Depreciation CSV
    $depResponse = $this->actingAs($this->admin)->get(route('reports.depreciation.export', ['format' => 'csv']));
    $depResponse->assertOk();
    $depResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $depCsv = file_get_contents($depResponse->getFile()->getPathname());
    expect($depCsv)->toContain('BBH-TEST-001');
    expect($depCsv)->toContain('Infusion Pump');
    expect($depCsv)->toContain('Cost (PHP)');

    // Assets CSV
    $assetsResponse = $this->actingAs($this->admin)->get(route('reports.export', ['format' => 'csv']));
    $assetsResponse->assertOk();
    $assetsResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $assetsCsv = file_get_contents($assetsResponse->getFile()->getPathname());
    expect($assetsCsv)->toContain('BBH-TEST-001');
    expect($assetsCsv)->toContain('Infusion Pump');

    // Template CSV
    $tmplResponse = $this->actingAs($this->admin)->get(route('reports.template', ['format' => 'csv']));
    $tmplResponse->assertOk();
    $tmplResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $tmplCsv = file_get_contents($tmplResponse->getFile()->getPathname());
    expect($tmplCsv)->toContain('asset_no');
    expect($tmplCsv)->toContain('Patient Monitor');
});

test('reports view displays Raw data (CSV) and Export Excel (.xlsx) buttons', function () {
    $response = $this->actingAs($this->admin)->get(route('reports.depreciation'));
    $response->assertOk();
    $response->assertSee('Raw data (CSV)');
    $response->assertSee('Export Excel (.xlsx)');

    $indexResponse = $this->actingAs($this->admin)->get(route('reports.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee('Raw data (CSV)');
    $indexResponse->assertSee('Export to Excel (.xlsx)');
});
