<?php

use App\Models\Asset;
use App\Services\DepreciationResult;
use App\Services\DepreciationService;
use Carbon\Carbon;

uses(Tests\TestCase::class);

test('calculates normal straight-line depreciation correctly using elapsed months', function () {
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

test('handles exactly end of life and past end of life by clamping to salvage value', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2010-01-01');
    $asOf = Carbon::parse('2020-01-01'); // exactly 10 years (120 months)
    $resultEnd = $service->calculate(100000, 10000, 10, $acquired, $asOf);

    expect($resultEnd->getCurrentValue())->toEqualWithDelta(10000.00, 0.01);
    expect($resultEnd->getAccumulatedDepreciation())->toEqualWithDelta(90000.00, 0.01);
    expect($resultEnd->getUsagePercentage())->toEqualWithDelta(100.00, 0.01);

    // 15 years later (5 years past useful life)
    $asOfPast = Carbon::parse('2025-01-01');
    $resultPast = $service->calculate(100000, 10000, 10, $acquired, $asOfPast);
    expect($resultPast->getCurrentValue())->toEqualWithDelta(10000.00, 0.01);
    expect($resultPast->getAccumulatedDepreciation())->toEqualWithDelta(90000.00, 0.01);
    expect($resultPast->getUsagePercentage())->toEqualWithDelta(100.00, 0.01);
});

test('handles zero salvage value', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2024-01-01');
    $asOf = Carbon::parse('2025-01-01'); // 1 year (12 months) of 5 years (60 months)
    $result = $service->calculate(50000, 0, 5, $acquired, $asOf);

    expect($result->getCurrentValue())->toEqualWithDelta(40000.00, 0.01);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(10000.00, 0.01);
});

test('handles missing cost, missing date, or missing useful life without crashing', function () {
    $service = new DepreciationService();

    $noCost = $service->calculate(null, 0, 5, Carbon::now());
    expect($noCost->isCalculable())->toBeFalse();
    expect($noCost->getMissingReason())->toBe('No cost entered');
    expect($service->formatPeso(null))->toBe('No cost entered');

    $noDate = $service->calculate(50000, 0, 5, null);
    expect($noDate->isCalculable())->toBeFalse();
    expect($noDate->getMissingReason())->toBe('Missing acquisition date');

    $noLife = $service->calculate(50000, 0, null, Carbon::now());
    expect($noLife->isCalculable())->toBeFalse();
    expect($noLife->getMissingReason())->toBe('Missing useful life');
});

test('handles future acquisition date as no depreciation yet with current value equal to cost', function () {
    $service = new DepreciationService();
    $acquired = Carbon::now()->addMonths(6);
    $result = $service->calculate(60000, 5000, 5, $acquired, Carbon::now());

    expect($result->isCalculable())->toBeTrue();
    expect($result->getMonthsElapsed())->toBe(0);
    expect($result->getAccumulatedDepreciation())->toEqualWithDelta(0.00, 0.01);
    expect($result->getCurrentValue())->toEqualWithDelta(60000.00, 0.01);
});

test('rounds peso decimal cents accurately and tolerates floating point variations', function () {
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

test('calculates depreciation for Asset model instance', function () {
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
