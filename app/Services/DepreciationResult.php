<?php

namespace App\Services;

use Carbon\CarbonInterface;

class DepreciationResult
{
    public function __construct(
        protected bool $calculable,
        protected ?string $missingReason = null,
        protected ?float $cost = null,
        protected ?float $salvageValue = null,
        protected ?int $usefulLifeYears = null,
        protected ?float $yearlyDepreciation = null,
        protected ?float $monthlyDepreciation = null,
        protected int $monthsElapsed = 0,
        protected ?float $accumulatedDepreciation = null,
        protected ?float $currentValue = null,
        protected float $usagePercentage = 0.0,
        protected ?CarbonInterface $startDate = null,
        protected ?CarbonInterface $endDate = null,
    ) {}

    public function isCalculable(): bool
    {
        return $this->calculable;
    }

    public function getMissingReason(): ?string
    {
        return $this->missingReason;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function getSalvageValue(): ?float
    {
        return $this->salvageValue;
    }

    public function getDisposalValue(): ?float
    {
        return $this->salvageValue;
    }

    public function getUsefulLifeYears(): ?int
    {
        return $this->usefulLifeYears;
    }

    public function getTotalMonths(): int
    {
        return ($this->usefulLifeYears ?? 0) * 12;
    }

    public function getStartDate(): ?CarbonInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?CarbonInterface
    {
        return $this->endDate;
    }

    public function getYearlyDepreciation(): ?float
    {
        return $this->yearlyDepreciation;
    }

    public function getMonthlyDepreciation(): ?float
    {
        return $this->monthlyDepreciation;
    }

    public function getMonthsElapsed(): int
    {
        return $this->monthsElapsed;
    }

    public function getAccumulatedDepreciation(): ?float
    {
        return $this->accumulatedDepreciation;
    }

    public function getCurrentValue(): ?float
    {
        return $this->currentValue;
    }

    public function getUsagePercentage(): float
    {
        return $this->usagePercentage;
    }
}
