<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DepreciationService
{
    /**
     * Calculate straight-line depreciation given cost, salvage value, useful life in years,
     * and acquisition date relative to an as-of date (defaulting to now).
     */
    public function calculate(
        mixed $cost,
        mixed $salvageValue,
        mixed $usefulLifeYears,
        mixed $dateAcquired,
        mixed $asOfDate = null
    ): DepreciationResult {
        if ($cost === null || $cost === '') {
            return new DepreciationResult(
                calculable: false,
                missingReason: 'No cost entered',
                cost: null,
                salvageValue: $salvageValue !== null ? (float) $salvageValue : null,
                usefulLifeYears: $usefulLifeYears !== null ? (int) $usefulLifeYears : null,
            );
        }

        $costFloat = round((float) $cost, 2);

        if ($dateAcquired === null || $dateAcquired === '') {
            return new DepreciationResult(
                calculable: false,
                missingReason: 'Missing acquisition date',
                cost: $costFloat,
                salvageValue: $salvageValue !== null ? (float) $salvageValue : null,
                usefulLifeYears: $usefulLifeYears !== null ? (int) $usefulLifeYears : null,
            );
        }

        if ($usefulLifeYears === null || (int) $usefulLifeYears <= 0) {
            return new DepreciationResult(
                calculable: false,
                missingReason: 'Missing useful life',
                cost: $costFloat,
                salvageValue: $salvageValue !== null ? (float) $salvageValue : null,
                usefulLifeYears: null,
            );
        }

        $lifeYears = (int) $usefulLifeYears;
        $salvageFloat = round(max(0.0, (float) ($salvageValue ?? 0.0)), 2);
        if ($salvageFloat > $costFloat) {
            $salvageFloat = $costFloat;
        }

        $depreciableAmount = round(max(0.0, $costFloat - $salvageFloat), 2);
        $totalMonths = max(1, $lifeYears * 12);
        $yearlyRate = round($depreciableAmount / $lifeYears, 2);
        $monthlyRate = $depreciableAmount / $totalMonths;

        $acquired = $dateAcquired instanceof CarbonInterface
            ? $dateAcquired->copy()
            : Carbon::parse($dateAcquired);

        $asOf = $asOfDate instanceof CarbonInterface
            ? $asOfDate->copy()
            : ($asOfDate ? Carbon::parse($asOfDate) : Carbon::now());

        // Future acquisition date: no depreciation yet
        if ($acquired->greaterThan($asOf)) {
            return new DepreciationResult(
                calculable: true,
                missingReason: null,
                cost: $costFloat,
                salvageValue: $salvageFloat,
                usefulLifeYears: $lifeYears,
                yearlyDepreciation: $yearlyRate,
                monthlyDepreciation: round($monthlyRate, 2),
                monthsElapsed: 0,
                accumulatedDepreciation: 0.00,
                currentValue: $costFloat,
                usagePercentage: 0.0,
            );
        }

        $monthsElapsed = (int) $acquired->diffInMonths($asOf);

        if ($monthsElapsed >= $totalMonths) {
            // Past or reached end of life
            $accumulated = $depreciableAmount;
            $currentValue = $salvageFloat;
            $usagePercentage = 100.0;
        } else {
            $accumulated = min($depreciableAmount, round($monthlyRate * $monthsElapsed, 2));
            $currentValue = max($salvageFloat, round($costFloat - $accumulated, 2));
            $usagePercentage = $depreciableAmount > 0
                ? min(100.0, round(($accumulated / $depreciableAmount) * 100, 2))
                : 100.0;
        }

        return new DepreciationResult(
            calculable: true,
            missingReason: null,
            cost: $costFloat,
            salvageValue: $salvageFloat,
            usefulLifeYears: $lifeYears,
            yearlyDepreciation: $yearlyRate,
            monthlyDepreciation: round($monthlyRate, 2),
            monthsElapsed: $monthsElapsed,
            accumulatedDepreciation: $accumulated,
            currentValue: $currentValue,
            usagePercentage: $usagePercentage,
        );
    }

    /**
     * Calculate depreciation for an Asset model instance.
     */
    public function calculateForAsset(Asset $asset, mixed $asOfDate = null): DepreciationResult
    {
        return $this->calculate(
            $asset->cost,
            $asset->salvage_value,
            $asset->useful_life_years,
            $asset->date_acquired,
            $asOfDate
        );
    }

    /**
     * Format a monetary amount as Philippine Pesos (₱) with thousands separators and 2 decimals.
     */
    public function formatPeso(?float $amount): string
    {
        if ($amount === null) {
            return 'No cost entered';
        }

        return '₱' . number_format($amount, 2);
    }
}
