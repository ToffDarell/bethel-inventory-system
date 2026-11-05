<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Services\DepreciationResult;
use App\Services\DepreciationService;
use Carbon\Carbon;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// Case 1: Normal straight-line (120000 cost, 10000 salvage, 10 yrs life, 3 yrs elapsed = 87000.00)
test('Case 1: calculates normal straight-line depreciation correctly using elapsed months', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2023-01-01');
    $asOf = Carbon::parse('2026-01-01'); // exactly 3 years (36 months)

    // Cost: 120,000, Salvage: 10,000, Life: 10 years
    // Depreciable = 110,000
    // Yearly = 11,000, Monthly = 110,000 / 120 = 916.666...
    // 36 months elapsed = 33,000 accumulated
    // Current value = 120,000 - 33,000 = 87,000
    $result = $service->calculate(120000, 10000, 10, $acquired, $asOf);

    expect($result->isCalculable())->toBeTrue();
    expect($result->getCurrentValue())->toEqualWithDelta(87000.00, 0.01);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(33000.00, 0.01);
    expect($result->getYearlyDepreciation())->toEqualWithDelta(11000.00, 0.01);
    expect($service->formatPeso($result->getCurrentValue()))->toBe('₱87,000.00');
});

// Case 2: Exactly at end of life
test('Case 2: handles exactly at end of life by clamping current value to salvage value', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2010-01-01');
    $asOf = Carbon::parse('2020-01-01'); // exactly 10 years (120 months)
    $resultEnd = $service->calculate(100000, 10000, 10, $acquired, $asOf);

    expect($resultEnd->isCalculable())->toBeTrue();
    expect($resultEnd->getCurrentValue())->toEqualWithDelta(10000.00, 0.01);
    expect($resultEnd->getAccumulatedDepreciation())->toEqualWithDelta(90000.00, 0.01);
    expect($resultEnd->getUsagePercentage())->toEqualWithDelta(100.00, 0.01);
});

// Case 3: Past end of life
test('Case 3: handles past end of life by clamping current value to salvage value and accumulated to depreciable base', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2010-01-01');
    // 15 years later (5 years past 10-year useful life)
    $asOfPast = Carbon::parse('2025-01-01');
    $resultPast = $service->calculate(100000, 10000, 10, $acquired, $asOfPast);

    expect($resultPast->isCalculable())->toBeTrue();
    expect($resultPast->getCurrentValue())->toEqualWithDelta(10000.00, 0.01);
    expect($resultPast->getAccumulatedDepreciation())->toEqualWithDelta(90000.00, 0.01);
    expect($resultPast->getUsagePercentage())->toEqualWithDelta(100.00, 0.01);
});

// Case 4: Zero salvage value
test('Case 4: handles zero salvage value with complete straight-line depreciation', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2024-01-01');
    $asOf = Carbon::parse('2025-01-01'); // 1 year (12 months) of 5 years (60 months)
    $result = $service->calculate(50000, 0, 5, $acquired, $asOf);

    expect($result->isCalculable())->toBeTrue();
    expect($result->getCurrentValue())->toEqualWithDelta(40000.00, 0.01);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(10000.00, 0.01);
});

// Case 5: Missing acquisition date
test('Case 5: handles missing acquisition date without crashing and returns appropriate missing hint', function () {
    $service = new DepreciationService();
    $noDate = $service->calculate(50000, 0, 5, null);

    expect($noDate->isCalculable())->toBeFalse();
    expect($noDate->getMissingReason())->toBe('Missing acquisition date');
    expect($noDate->getCurrentValue())->toBeNull();
    expect($noDate->getAccumulatedDepreciation())->toBeNull();
});

// Case 6: Missing cost
test('Case 6: handles missing cost without crashing and returns No cost entered hint', function () {
    $service = new DepreciationService();
    $noCost = $service->calculate(null, 0, 5, Carbon::now());

    expect($noCost->isCalculable())->toBeFalse();
    expect($noCost->getMissingReason())->toBe('No cost entered');
    expect($service->formatPeso(null))->toBe('No cost entered');
    expect($noCost->getCurrentValue())->toBeNull();
    expect($noCost->getAccumulatedDepreciation())->toBeNull();
});

// Case 7: Future acquisition date (no depreciation yet)
test('Case 7: handles future acquisition date as no depreciation yet with current value equal to cost', function () {
    $service = new DepreciationService();
    $acquired = Carbon::now()->addMonths(6);
    $result = $service->calculate(60000, 5000, 5, $acquired, Carbon::now());

    expect($result->isCalculable())->toBeTrue();
    expect($result->getMonthsElapsed())->toBe(0);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(0.00, 0.01);
    expect($result->getCurrentValue())->toEqualWithDelta(60000.00, 0.01);
});

// Case 8: Decimal rounding and floating-point tolerance
test('Case 8: rounds peso decimal cents accurately and tolerates floating point variations', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2025-01-01');
    $asOf = Carbon::parse('2025-04-01'); // 3 months
    // Cost: 10000.33, Salvage: 0, Life: 3 years (36 months)
    // Monthly = 10000.33 / 36 = 277.786944...
    // 3 months = 833.3608... rounded to 833.36
    // Current value = 10000.33 - 833.36 = 9166.97
    $result = $service->calculate(10000.33, 0, 3, $acquired, $asOf);

    expect($result->isCalculable())->toBeTrue();
    expect($result->getCurrentValue())->toEqualWithDelta(9166.97, 0.01);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(833.36, 0.01);
});

// Additional Case 9: Asset Eloquent model instance
test('Case 9: calculates depreciation for Asset model instance', function () {
    $service = new DepreciationService();
    $asset = new Asset([
        'cost' => 120000,
        'salvage_value' => 10000,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
    ]);

    $result = $service->calculateForAsset($asset, Carbon::parse('2026-01-01'));
    expect($result->isCalculable())->toBeTrue();
    expect($result->getCurrentValue())->toEqualWithDelta(87000.00, 0.01);
});

// Client Field Specs: Year-only acquisition date stored as January 1 of that year
test('calculates depreciation for year-only acquisition date stored as January 1', function () {
    $service = new DepreciationService();
    // Bought 3 years ago on January 1: 2023-01-01, as of 2026-01-01
    // Cost: 120,000, Salvage: 10,000, Useful life: 10 years
    $result = $service->calculate(
        cost: 120000,
        salvageValue: 10000,
        usefulLifeYears: 10,
        dateAcquired: '2023-01-01',
        asOfDate: Carbon::parse('2026-01-01')
    );

    expect($result->isCalculable())->toBeTrue()
        ->and($result->getMonthsElapsed())->toBe(36)
        ->and($result->getAccumulatedDepreciation())->toEqualWithDelta(33000.00, 0.01)
        ->and($result->getCurrentValue())->toEqualWithDelta(87000.00, 0.01);
});

test('calculates depreciation for full-date acquisition', function () {
    $service = new DepreciationService();
    // Acquired on 2023-07-01, as of 2024-07-01 (12 months)
    // Cost: 120,000, Salvage: 10,000, Life: 10 years
    $result = $service->calculate(
        cost: 120000,
        salvageValue: 10000,
        usefulLifeYears: 10,
        dateAcquired: '2023-07-01',
        asOfDate: Carbon::parse('2024-07-01')
    );

    expect($result->isCalculable())->toBeTrue()
        ->and($result->getMonthsElapsed())->toBe(12)
        ->and($result->getAccumulatedDepreciation())->toEqualWithDelta(11000.00, 0.01)
        ->and($result->getCurrentValue())->toEqualWithDelta(109000.00, 0.01);
});

test('handles future year acquisition with zero depreciation', function () {
    $service = new DepreciationService();
    $futureYear = Carbon::now()->year + 2;
    $result = $service->calculate(
        cost: 120000,
        salvageValue: 10000,
        usefulLifeYears: 10,
        dateAcquired: "{$futureYear}-01-01",
        asOfDate: Carbon::now()
    );

    expect($result->isCalculable())->toBeTrue()
        ->and($result->getMonthsElapsed())->toBe(0)
        ->and($result->getAccumulatedDepreciation())->toEqualWithDelta(0.00, 0.01)
        ->and($result->getCurrentValue())->toEqualWithDelta(120000.00, 0.01);
});

test('handles missing acquisition date as not calculable', function () {
    $service = new DepreciationService();
    $result = $service->calculate(
        cost: 120000,
        salvageValue: 10000,
        usefulLifeYears: 10,
        dateAcquired: null
    );

    expect($result->isCalculable())->toBeFalse()
        ->and($result->getMissingReason())->toBe('Missing acquisition date')
        ->and($result->getCurrentValue())->toBeNull();
});

test('default policy behavior is unchanged with zero settings configured', function () {
    $service = new DepreciationService();

    expect($service->getStartConvention())->toBe('january_1');
    expect($service->getMinCost())->toBe(0.0);
    expect($service->getDefaultSalvagePercent())->toBe(0.0);
    expect($service->getConventionLabel())->toContain('January 1');

    $asset = new Asset([
        'cost' => 120000,
        'salvage_value' => 0,
        'useful_life_years' => 10,
        'date_acquired' => '2020-01-01',
        'acquired_year_only' => true,
    ]);

    $result = $service->calculateForAsset($asset, Carbon::parse('2022-01-01'));
    expect($result->isCalculable())->toBeTrue();
    expect($result->getMonthsElapsed())->toBe(24);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(24000.00, 0.01);
    expect($result->getCurrentValue())->toEqualWithDelta(96000.00, 0.01);
});

test('respects start conventions (january_1, july_1, december_31) for year-only assets', function () {
    $service = new DepreciationService();
    $asOf = Carbon::parse('2024-01-01');

    // 1. January 1 (default): 2023-01-01 to 2024-01-01 = 12 months
    Setting::set('depreciation_start_convention', 'january_1');
    $resJan = $service->calculate(
        cost: 120000,
        salvageValue: 0,
        usefulLifeYears: 10,
        dateAcquired: '2023-01-01',
        asOfDate: $asOf,
        acquiredYearOnly: true
    );
    expect($resJan->getMonthsElapsed())->toBe(12);
    expect($resJan->getAccumulatedDepreciation())->toEqualWithDelta(12000.00, 0.01);

    // 2. July 1 (mid-year): 2023-07-01 to 2024-01-01 = 6 months
    Setting::set('depreciation_start_convention', 'july_1');
    $resJul = $service->calculate(
        cost: 120000,
        salvageValue: 0,
        usefulLifeYears: 10,
        dateAcquired: '2023-01-01',
        asOfDate: $asOf,
        acquiredYearOnly: true
    );
    expect($resJul->getMonthsElapsed())->toBe(6);
    expect($resJul->getAccumulatedDepreciation())->toEqualWithDelta(6000.00, 0.01);

    // 3. December 31 (end of year): 2023-12-31 to 2024-01-01 = 0 months
    Setting::set('depreciation_start_convention', 'december_31');
    $resDec = $service->calculate(
        cost: 120000,
        salvageValue: 0,
        usefulLifeYears: 10,
        dateAcquired: '2023-01-01',
        asOfDate: $asOf,
        acquiredYearOnly: true
    );
    expect($resDec->getMonthsElapsed())->toBe(0);
    expect($resDec->getAccumulatedDepreciation())->toEqualWithDelta(0.00, 0.01);

    // 4. Custom Month and Day (e.g. April 1): 2023-04-01 to 2024-01-01 = 9 months
    Setting::set('depreciation_start_convention', 'custom');
    Setting::set('depreciation_custom_month', '4');
    Setting::set('depreciation_custom_day', '1');
    $resCustom = $service->calculate(
        cost: 120000,
        salvageValue: 0,
        usefulLifeYears: 10,
        dateAcquired: '2023-01-01',
        asOfDate: $asOf,
        acquiredYearOnly: true
    );
    expect($resCustom->getMonthsElapsed())->toBe(9);
    expect($resCustom->getAccumulatedDepreciation())->toEqualWithDelta(9000.00, 0.01);
    expect($service->getConventionLabel('custom'))->toBe('Counted from April 1 of the Year Bought (Custom convention)');

    // Clean up
    Setting::set('depreciation_start_convention', 'january_1');
    Setting::set('depreciation_custom_month', '1');
    Setting::set('depreciation_custom_day', '1');
});

test('exact acquisition dates are never shifted by the start convention', function () {
    $service = new DepreciationService();
    $asOf = Carbon::parse('2024-01-01');

    // Setting is july_1
    Setting::set('depreciation_start_convention', 'july_1');

    // Asset imported with full date January 1 (acquired_year_only is false)
    $assetExact = new Asset([
        'cost' => 120000,
        'salvage_value' => 0,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
        'acquired_year_only' => false,
    ]);

    $result = $service->calculateForAsset($assetExact, $asOf);
    // Because it is NOT year-only, it retains 2023-01-01 (12 months), NOT shifted to July 1 (6 months)
    expect($result->getMonthsElapsed())->toBe(12);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(12000.00, 0.01);

    // Clean up
    Setting::set('depreciation_start_convention', 'january_1');
});

test('minimum cost rule excludes assets below threshold with Below minimum cost reason', function () {
    $service = new DepreciationService();
    Setting::set('depreciation_min_cost', '10000.00');

    // Asset with cost 5,000 (below 10,000 threshold)
    $lowCostAsset = new Asset([
        'cost' => 5000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'date_acquired' => '2022-01-01',
        'acquired_year_only' => true,
    ]);

    $resultLow = $service->calculateForAsset($lowCostAsset);
    expect($resultLow->isCalculable())->toBeFalse();
    expect($resultLow->getMissingReason())->toBe('Below minimum cost');
    expect($resultLow->getCost())->toEqualWithDelta(5000.00, 0.01);

    // Asset with cost 15,000 (above 10,000 threshold)
    $highCostAsset = new Asset([
        'cost' => 15000,
        'salvage_value' => 0,
        'useful_life_years' => 5,
        'date_acquired' => '2022-01-01',
        'acquired_year_only' => true,
    ]);

    $resultHigh = $service->calculateForAsset($highCostAsset);
    expect($resultHigh->isCalculable())->toBeTrue();
    expect($resultHigh->getMissingReason())->toBeNull();

    // Clean up
    Setting::set('depreciation_min_cost', '0.00');
});

test('salvage precedence resolves category default, then global default, then 0', function () {
    $service = new DepreciationService();

    // Case 1: Category default 15%, Global default 10% -> Category wins (15%)
    Setting::set('depreciation_default_salvage_percent', '10');
    $categoryWithSalvage = new \App\Models\Category(['name' => 'Diagnostic', 'default_salvage_percent' => 15]);
    expect($service->resolveDefaultSalvagePercent($categoryWithSalvage))->toEqualWithDelta(15.0, 0.01);

    // Case 2: Category has no salvage (0/null), Global default 10% -> Global wins (10%)
    $categoryWithoutSalvage = new \App\Models\Category(['name' => 'General', 'default_salvage_percent' => 0]);
    expect($service->resolveDefaultSalvagePercent($categoryWithoutSalvage))->toEqualWithDelta(10.0, 0.01);

    // Case 3: No category (null), Global default 10% -> Global wins (10%)
    expect($service->resolveDefaultSalvagePercent(null))->toEqualWithDelta(10.0, 0.01);

    // Case 4: Category has no salvage, Global default 0 -> 0%
    Setting::set('depreciation_default_salvage_percent', '0');
    expect($service->resolveDefaultSalvagePercent($categoryWithoutSalvage))->toEqualWithDelta(0.0, 0.01);
    expect($service->resolveDefaultSalvagePercent(null))->toEqualWithDelta(0.0, 0.01);
});
