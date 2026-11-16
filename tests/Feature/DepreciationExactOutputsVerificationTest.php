<?php

use App\Exports\DepreciationExport;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    Setting::set('depreciation_use_category_rules', '1');
    Setting::set('depreciation_start_convention', 'january_1');
    Setting::set('depreciation_min_cost', '0');
    Setting::set('depreciation_default_salvage_percent', '10');
});

test('exact depreciation values across all outputs for defibrillator and aircon examples', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $medCat = Category::firstOrCreate(
        ['name' => 'Medical Equipment'],
        ['default_useful_life_years' => 10, 'default_salvage_percent' => 10.00]
    );
    $medCat->update(['default_useful_life_years' => 10, 'default_salvage_percent' => 10.00]);

    $applianceCat = Category::firstOrCreate(
        ['name' => 'Appliances'],
        ['default_useful_life_years' => 8, 'default_salvage_percent' => 10.00]
    );
    $applianceCat->update(['default_useful_life_years' => 8, 'default_salvage_percent' => 10.00]);

    // Defibrillator: cost 120000, 10 yrs, 10% disposal, 46 months -> disposal 12000, accum 41400, current 78600.00
    $defib = Asset::factory()->create([
        'asset_no' => 'BBH-DEFIB-01',
        'asset_name' => 'Defibrillator HeartStart XL',
        'category_id' => $medCat->id,
        'cost' => 120000.00,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => false,
        'status' => 'In Service',
    ]);

    // Aircon: cost 15000, 8 yrs, 10% disposal, 46 months -> disposal 1500, accum 6468.75, current 8531.25
    $aircon = Asset::factory()->create([
        'asset_no' => 'BBH-AIRCON-01',
        'asset_name' => 'Carrier Aircon 1.5HP',
        'category_id' => $applianceCat->id,
        'cost' => 15000.00,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => false,
        'status' => 'In Service',
    ]);

    $asOfDate = Carbon::create(2026, 11, 1, 0, 0, 0); // Exactly 46 months from 2023-01-01

    $depService = app(DepreciationService::class);

    // 1. Direct Service calculation
    $defibDep = $depService->calculateForAsset($defib, $asOfDate);
    expect($defibDep->isCalculable())->toBeTrue();
    expect($defibDep->getMonthsElapsed())->toBe(46);
    expect($defibDep->getDisposalValue())->toBe(12000.00);
    expect($defibDep->getAccumulatedDepreciation())->toBe(41400.00);
    expect($defibDep->getCurrentValue())->toBe(78600.00);

    $airconDep = $depService->calculateForAsset($aircon, $asOfDate);
    expect($airconDep->isCalculable())->toBeTrue();
    expect($airconDep->getMonthsElapsed())->toBe(46);
    expect($airconDep->getDisposalValue())->toBe(1500.00);
    expect($airconDep->getAccumulatedDepreciation())->toBe(6468.75);
    expect($airconDep->getCurrentValue())->toBe(8531.25);

    // 2. Asset Detail Card HTML (show.blade.php)
    Carbon::setTestNow($asOfDate);
    $defibResponse = $this->actingAs($admin)->get(route('assets.show', $defib));
    $defibResponse->assertOk();
    $defibResponse->assertSee('₱78,600.00');
    $defibResponse->assertSee('₱41,400.00');
    $defibResponse->assertSee('₱12,000.00');

    $airconResponse = $this->actingAs($admin)->get(route('assets.show', $aircon));
    $airconResponse->assertOk();
    $airconResponse->assertSee('₱8,531.25');
    $airconResponse->assertSee('₱6,468.75');
    $airconResponse->assertSee('₱1,500.00');

    // 3. Depreciation Report HTML (reports.depreciation)
    $reportResponse = $this->actingAs($admin)->get(route('reports.depreciation', ['as_of' => '2026-11-01']));
    $reportResponse->assertOk();
    $reportResponse->assertSee('78,600.00');
    $reportResponse->assertSee('41,400.00');
    $reportResponse->assertSee('12,000.00');
    $reportResponse->assertSee('8,531.25');
    $reportResponse->assertSee('6,468.75');
    $reportResponse->assertSee('1,500.00');

    // 4. Excel Export (DepreciationMainSheet)
    $export = new DepreciationExport(null, null, $asOfDate, 'xlsx', $admin);
    $sheets = $export->sheets();
    expect(count($sheets))->toBeGreaterThanOrEqual(1);

    // 5. CSV Export (DepreciationRawCsvSheet)
    $csvSheet = new \App\Exports\Sheets\DepreciationRawCsvSheet(null, null, $asOfDate);
    $rows = $csvSheet->array();
    $defibRow = collect($rows)->firstWhere('0', 'BBH-DEFIB-01');
    expect($defibRow)->not->toBeNull();
    expect($defibRow[9])->toBe('12000.00');
    expect($defibRow[10])->toBe('41400.00');
    expect($defibRow[11])->toBe('78600.00');

    $airconRow = collect($rows)->firstWhere('0', 'BBH-AIRCON-01');
    expect($airconRow)->not->toBeNull();
    expect($airconRow[9])->toBe('1500.00');
    expect($airconRow[10])->toBe('6468.75');
    expect($airconRow[11])->toBe('8531.25');

    // 6. PDF Export
    $pdfResponse = $this->actingAs($admin)->get('/reports/depreciation/export?format=pdf&as_of=2026-11-01');
    $pdfResponse->assertOk();
    expect($pdfResponse->headers->get('content-type'))->toBe('application/pdf');

    // 7. Dashboard HTML contains no peso amounts for admin and for staff
    $dashResponse = $this->actingAs($admin)->get('/dashboard');
    $dashResponse->assertOk();
    $dashResponse->assertDontSee('Total Asset Value');
    $dashResponse->assertDontSee('₱');
    $dashResponse->assertDontSee('78,600.00');
    $dashResponse->assertDontSee('8,531.25');

    $staffUser = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $staffDashResponse = $this->actingAs($staffUser)->get('/dashboard');
    $staffDashResponse->assertOk();
    $staffDashResponse->assertDontSee('Total Asset Value');
    $staffDashResponse->assertDontSee('₱');
    $staffDashResponse->assertDontSee('78,600.00');
    $staffDashResponse->assertDontSee('8,531.25');

    Carbon::setTestNow();
});
