<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Setting;
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
        mixed $asOfDate = null,
        ?bool $acquiredYearOnly = null
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

        $minCost = $this->getMinCost();
        if ($minCost > 0 && $costFloat < $minCost) {
            return new DepreciationResult(
                calculable: false,
                missingReason: 'Below minimum cost',
                cost: $costFloat,
                salvageValue: $salvageValue !== null ? (float) $salvageValue : null,
                usefulLifeYears: $usefulLifeYears !== null ? (int) $usefulLifeYears : null,
            );
        }

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

        // Apply start convention at calculation time only to year-only rows
        $isYearOnly = $acquiredYearOnly ?? (is_numeric($dateAcquired) && strlen((string) $dateAcquired) === 4);
        if ($isYearOnly) {
            $convention = $this->getStartConvention();
            $year = $acquired->year;
            $acquired = match ($convention) {
                'july_1' => Carbon::create($year, 7, 1, 0, 0, 0),
                'december_31' => Carbon::create($year, 12, 31, 0, 0, 0),
                'custom' => Carbon::create($year, $this->getCustomMonth(), $this->getCustomDay(), 0, 0, 0),
                default => Carbon::create($year, 1, 1, 0, 0, 0),
            };
        }

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
            $asOfDate,
            $asset->acquired_year_only
        );
    }

    /**
     * Active depreciation policy getters
     */
    public function getStartConvention(): string
    {
        return Setting::get('depreciation_start_convention', 'january_1');
    }

    public function getMinCost(): float
    {
        return (float) Setting::get('depreciation_min_cost', 0);
    }

    public function getDefaultSalvagePercent(): float
    {
        return (float) Setting::get('depreciation_default_salvage_percent', 0);
    }

    public function getCustomMonth(): int
    {
        return max(1, min(12, (int) Setting::get('depreciation_custom_month', 1)));
    }

    public function getCustomDay(): int
    {
        return max(1, min(31, (int) Setting::get('depreciation_custom_day', 1)));
    }

    public function getConventionLabel(?string $convention = null): string
    {
        $conv = $convention ?? $this->getStartConvention();
        if ($conv === 'custom') {
            $month = $this->getCustomMonth();
            $day = $this->getCustomDay();
            $dateFormatted = Carbon::create(2000, $month, $day)->format('F j');
            return "Counted from {$dateFormatted} of the Year Bought (Custom convention)";
        }

        return match ($conv) {
            'july_1' => 'Counted from July 1 of the Year Bought (Half-Year convention)',
            'december_31' => 'Counted from December 31 of the Year Bought (Year-End convention)',
            default => 'Counted from January 1 of the Year Bought (Full-Year convention)',
        };
    }

    public function resolveDefaultSalvagePercent(?Category $category): float
    {
        if ($category && $category->default_salvage_percent !== null && (float) $category->default_salvage_percent > 0) {
            return (float) $category->default_salvage_percent;
        }

        $global = $this->getDefaultSalvagePercent();
        if ($global > 0) {
            return $global;
        }

        return 0.0;
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
