<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Services\AssetImportService;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'System Administrator',
        'email' => 'admin-test@hospital.local',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Clinical Staff',
        'email' => 'staff-test@hospital.local',
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    Setting::set('depreciation_start_convention', 'january_1');
    Setting::set('depreciation_default_salvage_percent', '0');
    Setting::set('depreciation_min_cost', '0.00');

    $this->category = Category::create([
        'name' => 'General Medical Equipment ' . uniqid(),
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 0.0,
    ]);
});

test('migration adds acquired_year_only and backfills January 1 dates as true on a copy of existing database', function () {
    // 1. Set up a separate copy database file
    $tempDbPath = storage_path('framework/testing_migration_copy.sqlite');
    if (File::exists($tempDbPath)) {
        File::delete($tempDbPath);
    }
    touch($tempDbPath);

    config(['database.connections.sqlite_copy' => [
        'driver' => 'sqlite',
        'database' => $tempDbPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);

    $copyDb = DB::connection('sqlite_copy');

    // Create pre-migration assets table
    $copyDb->getSchemaBuilder()->create('assets', function ($table) {
        $table->id();
        $table->string('asset_no')->unique();
        $table->string('asset_name');
        $table->date('date_acquired')->nullable();
        $table->string('status')->default('In Use');
        $table->timestamps();
    });

    // Insert pre-migration records
    $copyDb->table('assets')->insert([
        [
            'asset_no' => 'COPY-JAN1',
            'asset_name' => 'Jan 1 Asset',
            'date_acquired' => '2023-01-01',
            'status' => 'In Use',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'asset_no' => 'COPY-JUN15',
            'asset_name' => 'June 15 Asset',
            'date_acquired' => '2023-06-15',
            'status' => 'In Use',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'asset_no' => 'COPY-NULL',
            'asset_name' => 'Null Date Asset',
            'date_acquired' => null,
            'status' => 'In Use',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Verify column does NOT exist before migration
    expect($copyDb->getSchemaBuilder()->hasColumn('assets', 'acquired_year_only'))->toBeFalse();

    // Now run our migration against this database
    $defaultConn = DB::getDefaultConnection();
    DB::setDefaultConnection('sqlite_copy');
    try {
        $migration = require database_path('migrations/2026_11_05_000001_add_acquired_year_only_to_assets_table.php');
        $migration->up();
    } finally {
        DB::setDefaultConnection($defaultConn);
    }

    // Verify column was added
    expect($copyDb->getSchemaBuilder()->hasColumn('assets', 'acquired_year_only'))->toBeTrue();

    // Verify backfill: Jan 1 is true, June 15 is false, Null is false
    $jan1Asset = $copyDb->table('assets')->where('asset_no', 'COPY-JAN1')->first();
    $jun15Asset = $copyDb->table('assets')->where('asset_no', 'COPY-JUN15')->first();
    $nullAsset = $copyDb->table('assets')->where('asset_no', 'COPY-NULL')->first();

    expect((bool) $jan1Asset->acquired_year_only)->toBeTrue();
    expect((bool) $jun15Asset->acquired_year_only)->toBeFalse();
    expect((bool) ($nullAsset->acquired_year_only ?? false))->toBeFalse();

    // Clean up
    if (File::exists($tempDbPath)) {
        File::delete($tempDbPath);
    }
});

test('import with full January 1 date stores acquired_year_only false and is not shifted by start convention', function () {
    $service = new DepreciationService();
    $importService = app(AssetImportService::class);

    // 1. Full date import: 2023-01-01 (acquired_year_only should be false)
    $rows = [
        [
            'asset_no' => 'BBH-EXACT-01',
            'asset_name' => 'Exact Date Ultrasound',
            'category' => $this->category->name,
            'cost' => 120000,
            'useful_life_years' => 10,
            'salvage_value' => 0,
            'date_acquired' => '2023-01-01',
            'acquired_year_only' => false,
            'status' => 'In Use',
            'is_valid' => true,
        ],
        [
            'asset_no' => 'BBH-YEAR-01',
            'asset_name' => 'Year Only Monitor',
            'category' => $this->category->name,
            'cost' => 120000,
            'useful_life_years' => 10,
            'salvage_value' => 0,
            'date_acquired' => '2023-01-01',
            'acquired_year_only' => true,
            'status' => 'In Use',
            'is_valid' => true,
        ],
    ];

    $importService->commit($rows, $this->admin);

    $exactAsset = Asset::where('asset_no', 'BBH-EXACT-01')->firstOrFail();
    $yearAsset = Asset::where('asset_no', 'BBH-YEAR-01')->firstOrFail();

    expect($exactAsset->acquired_year_only)->toBeFalse();
    expect($yearAsset->acquired_year_only)->toBeTrue();

    // Set convention to july_1
    Setting::set('depreciation_start_convention', 'july_1');
    $asOf = Carbon::parse('2024-01-01');

    $resExact = $service->calculateForAsset($exactAsset, $asOf);
    $resYear = $service->calculateForAsset($yearAsset, $asOf);

    // Exact date (2023-01-01) is NOT shifted: 12 months elapsed
    expect($resExact->getMonthsElapsed())->toBe(12);
    expect($resExact->getAccumulatedDepreciation())->toEqualWithDelta(12000.00, 0.01);

    // Year-only date IS shifted to 2023-07-01: 6 months elapsed
    expect($resYear->getMonthsElapsed())->toBe(6);
    expect($resYear->getAccumulatedDepreciation())->toEqualWithDelta(6000.00, 0.01);
});

test('admin can update depreciation policy and changes are logged in activity log', function () {
    $response = $this->actingAs($this->admin)->post('/settings/depreciation', [
        'depreciation_start_convention' => 'july_1',
        'depreciation_default_salvage_percent' => '12.50',
        'depreciation_min_cost' => '5000.00',
    ]);

    $response->assertRedirect('/settings?tab=depreciation');
    $response->assertSessionHas('success', 'Depreciation policy updated successfully.');

    expect(Setting::get('depreciation_start_convention'))->toBe('july_1');
    expect(Setting::get('depreciation_default_salvage_percent'))->toBe('12.5');
    expect(Setting::get('depreciation_min_cost'))->toBe('5000');

    // Verify activity logs
    $activities = Activity::where('causer_id', $this->admin->id)
        ->latest('id')
        ->take(3)
        ->pluck('description')
        ->all();

    $joined = implode(' | ', $activities);
    expect($joined)->toContain('Depreciation start convention changed from');
    expect($joined)->toContain('Default disposal percent changed from');
    expect($joined)->toContain('Minimum cost to depreciate changed from');
});

test('depreciation policy validation rejects negative minimum cost and salvage percent above 90', function () {
    // Negative minimum cost
    $resp1 = $this->actingAs($this->admin)->post('/settings/depreciation', [
        'depreciation_start_convention' => 'january_1',
        'depreciation_default_salvage_percent' => '10',
        'depreciation_min_cost' => '-100.00',
    ]);
    $resp1->assertSessionHasErrors('depreciation_min_cost');

    // Salvage percent above 90%
    $resp2 = $this->actingAs($this->admin)->post('/settings/depreciation', [
        'depreciation_start_convention' => 'january_1',
        'depreciation_default_salvage_percent' => '95',
        'depreciation_min_cost' => '100.00',
    ]);
    $resp2->assertSessionHasErrors('depreciation_default_salvage_percent');

    // Invalid start convention
    $resp3 = $this->actingAs($this->admin)->post('/settings/depreciation', [
        'depreciation_start_convention' => 'march_1',
        'depreciation_default_salvage_percent' => '10',
        'depreciation_min_cost' => '100.00',
    ]);
    $resp3->assertSessionHasErrors('depreciation_start_convention');

    // Invalid calendar date for custom convention (e.g. February 31)
    $resp4 = $this->actingAs($this->admin)->post('/settings/depreciation', [
        'depreciation_start_convention' => 'custom',
        'depreciation_custom_month' => 2,
        'depreciation_custom_day' => 31,
        'depreciation_default_salvage_percent' => '10',
        'depreciation_min_cost' => '100.00',
    ]);
    $resp4->assertSessionHasErrors('depreciation_custom_day');
});

test('admin can set custom start convention and it updates settings and activity log', function () {
    $response = $this->actingAs($this->admin)->post('/settings/depreciation', [
        'depreciation_start_convention' => 'custom',
        'depreciation_custom_month' => 10,
        'depreciation_custom_day' => 1,
        'depreciation_default_salvage_percent' => '5.00',
        'depreciation_min_cost' => '2500.00',
    ]);

    $response->assertRedirect('/settings?tab=depreciation');
    expect(Setting::get('depreciation_start_convention'))->toBe('custom');
    expect((int) Setting::get('depreciation_custom_month'))->toBe(10);
    expect((int) Setting::get('depreciation_custom_day'))->toBe(1);

    $activities = Activity::where('causer_id', $this->admin->id)
        ->latest('id')
        ->take(3)
        ->pluck('description')
        ->all();

    $joined = implode(' | ', $activities);
    expect($joined)->toContain('custom (October 1)');
});

test('staff cannot update depreciation policy', function () {
    $this->actingAs($this->staff)->post('/settings/depreciation', [
        'depreciation_start_convention' => 'july_1',
        'depreciation_default_salvage_percent' => '10',
        'depreciation_min_cost' => '5000',
    ])->assertForbidden();
});

test('depreciation settings form contains confirmation dialog attribute', function () {
    $response = $this->actingAs($this->admin)->get('/settings');
    $response->assertOk();
    $response->assertSee("onsubmit=\"return confirm('This changes the estimated values of all assets.');\"", false);
    $response->assertSee('Save Depreciation Policy');
});

test('depreciation report shows disclaimer, active policy in method note, and handles below minimum cost assets', function () {
    Setting::set('depreciation_min_cost', '10000.00');
    Setting::set('depreciation_start_convention', 'july_1');

    // Asset A: below 10,000 threshold
    $assetA = Asset::create([
        'asset_no' => 'BBH-LOW-01',
        'asset_name' => 'Digital Thermometer',
        'category_id' => $this->category->id,
        'cost' => 5000.00,
        'useful_life_years' => 3,
        'salvage_value' => 0,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => true,
        'status' => 'In Use',
    ]);

    // Asset B: above threshold
    $assetB = Asset::create([
        'asset_no' => 'BBH-HIGH-01',
        'asset_name' => 'Infusion Pump',
        'category_id' => $this->category->id,
        'cost' => 20000.00,
        'useful_life_years' => 5,
        'salvage_value' => 0,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => true,
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->admin)->get('/reports/depreciation');
    $response->assertOk();

    // 1. Disclaimer is visible
    $response->assertSee('Estimated values for tracking, not an official accounting record');

    // 2. Active policy is visible in method note
    $response->assertSee('Counted from July 1 of the Year Bought (Half-Year convention)');
    $response->assertSee('Minimum Cost:');
    $response->assertSee('₱10,000.00');

    // 3. Table shows below minimum cost label and cost
    $response->assertSee('BBH-LOW-01');
    $response->assertSee('₱5,000.00');
    $response->assertSee('Below minimum cost');

    // 4. Low cost asset is excluded from totals (Total Acquisition Cost = ₱20,000.00, not ₱25,000.00)
    $response->assertSee('₱20,000.00');

    // 5. Excluded note is visible
    $response->assertSee('1 asset excluded from totals because cost is below the ₱10,000.00 minimum depreciation threshold.');
});

test('depreciation excel and csv exports include disclaimer, active policy, and below min cost labels', function () {
    Setting::set('depreciation_min_cost', '10000.00');
    Setting::set('depreciation_start_convention', 'july_1');

    Asset::create([
        'asset_no' => 'BBH-LOW-02',
        'asset_name' => 'Pulse Oximeter',
        'category_id' => $this->category->id,
        'cost' => 4500.00,
        'useful_life_years' => 3,
        'salvage_value' => 0,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => true,
        'status' => 'In Use',
    ]);

    Asset::create([
        'asset_no' => 'BBH-HIGH-02',
        'asset_name' => 'Hospital Bed Electric',
        'category_id' => $this->category->id,
        'cost' => 50000.00,
        'useful_life_years' => 10,
        'salvage_value' => 5000,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => true,
        'status' => 'In Use',
    ]);

    $csvResp = $this->actingAs($this->admin)->get('/reports/depreciation/export?format=csv');
    $csvResp->assertOk();

    $csvContent = file_get_contents($csvResp->getFile()->getPathname());

    expect($csvContent)->toContain('DISCLAIMER: Estimated values for tracking, not an official accounting record');
    expect($csvContent)->toContain('Counted from July 1 of the Year Bought (Half-Year convention)');
    expect($csvContent)->toContain('Minimum Cost to Depreciate: PHP 10,000.00');
    expect($csvContent)->toContain('BBH-LOW-02');
    expect($csvContent)->toContain('4500');
    expect($csvContent)->toContain('Below minimum cost');
});
