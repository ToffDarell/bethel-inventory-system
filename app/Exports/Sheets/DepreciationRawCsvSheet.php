<?php

namespace App\Exports\Sheets;

use App\Models\Asset;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DepreciationRawCsvSheet implements FromArray, WithHeadings, WithTitle, Export
{
    protected DepreciationService $depreciationService;
    protected Carbon $asOfDate;

    public function __construct(
        protected ?string $categoryId = null,
        protected ?string $department = null,
        mixed $asOfDate = null,
    ) {
        $this->depreciationService = app(DepreciationService::class);
        if ($asOfDate instanceof Carbon) {
            $this->asOfDate = $asOfDate->copy()->startOfDay();
        } elseif ($asOfDate) {
            try {
                $this->asOfDate = Carbon::parse($asOfDate)->startOfDay();
            } catch (\Throwable) {
                $this->asOfDate = Carbon::today();
            }
        } else {
            $this->asOfDate = Carbon::today();
        }
    }

    public function title(): string
    {
        return 'Depreciation';
    }

    public function headings(): array
    {
        return [
            'Asset No.',
            'Asset Description',
            'Category',
            'Department',
            'Year Bought',
            'Depreciation Start Date',
            'Months Depreciated',
            'Useful Life (years)',
            'Cost (PHP)',
            'Disposal Value (PHP)',
            'Accumulated Depreciation (PHP)',
            'Current Value (PHP)',
            'Remarks',
        ];
    }

    public function array(): array
    {
        $query = Asset::query()
            ->with('category')
            ->where('status', '!=', 'Disposed')
            ->orderBy('asset_no');

        if ($this->categoryId !== null && $this->categoryId !== '') {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->department !== null && $this->department !== '') {
            $query->where('department', $this->department);
        }

        $assets = $query->get();
        $rows = [];

        foreach ($assets as $asset) {
            $dep = $this->depreciationService->calculateForAsset($asset, $this->asOfDate);

            $accumulated = '';
            $currentValue = '';

            if ($dep->isCalculable()) {
                $accumulated = number_format((float) $dep->getAccumulatedDepreciation(), 2, '.', '');
                $currentValue = number_format((float) $dep->getCurrentValue(), 2, '.', '');
            } elseif ($dep->getMissingReason() === 'Below minimum cost') {
                $accumulated = 'Below minimum cost';
                $currentValue = 'Below minimum cost';
            } elseif ($asset->cost !== null) {
                $currentValue = number_format((float) $asset->cost, 2, '.', '');
            }

            $rows[] = [
                $asset->asset_no,
                $asset->asset_name,
                $asset->category?->name ?? 'Uncategorized',
                $asset->department ?? '-',
                $asset->date_acquired ? $asset->date_acquired->format('Y') : '',
                $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : '-',
                $dep->isCalculable() ? (string) $dep->getMonthsElapsed() : '-',
                $dep->isCalculable() ? (string) $dep->getUsefulLifeYears() : ($asset->category?->default_useful_life_years !== null ? (string) $asset->category->default_useful_life_years : ''),
                $asset->cost !== null ? number_format((float) $asset->cost, 2, '.', '') : '',
                ($dep->isCalculable() && $dep->getDisposalValue() !== null) ? number_format((float) $dep->getDisposalValue(), 2, '.', '') : '-',
                $accumulated,
                $currentValue,
                $asset->notes ?? '',
            ];
        }

        // Trailing metadata notes
        $conventionLabel = $this->depreciationService->getConventionLabel();
        $minCost = $this->depreciationService->getMinCost();
        $minCostText = $minCost > 0 ? 'PHP ' . number_format($minCost, 2) : 'None (all assets)';

        $rows[] = ['DISCLAIMER: Estimated values for tracking, not an official accounting record'];
        $rows[] = ['POLICY: ' . $conventionLabel . ' | Minimum Cost to Depreciate: ' . $minCostText];

        return $rows;
    }

    /**
     * Backward-compatibility for callers invoking collection() or map() directly
     */
    public function collection(): Collection
    {
        return Asset::query()
            ->with('category')
            ->where('status', '!=', 'Disposed')
            ->orderBy('asset_no')
            ->get();
    }

    public function map($asset): array
    {
        $dep = $this->depreciationService->calculateForAsset($asset, $this->asOfDate);
        $accumulated = '';
        $currentValue = '';

        if ($dep->isCalculable()) {
            $accumulated = number_format((float) $dep->getAccumulatedDepreciation(), 2, '.', '');
            $currentValue = number_format((float) $dep->getCurrentValue(), 2, '.', '');
        } elseif ($dep->getMissingReason() === 'Below minimum cost') {
            $accumulated = 'Below minimum cost';
            $currentValue = 'Below minimum cost';
        } elseif ($asset->cost !== null) {
            $currentValue = number_format((float) $asset->cost, 2, '.', '');
        }

        return [
            $asset->asset_no,
            $asset->asset_name,
            $asset->category?->name ?? 'Uncategorized',
            $asset->department ?? '-',
            $asset->date_acquired ? $asset->date_acquired->format('Y') : '',
            $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : '-',
            $dep->isCalculable() ? (string) $dep->getMonthsElapsed() : '-',
            $dep->isCalculable() ? (string) $dep->getUsefulLifeYears() : ($asset->category?->default_useful_life_years !== null ? (string) $asset->category->default_useful_life_years : ''),
            $asset->cost !== null ? number_format((float) $asset->cost, 2, '.', '') : '',
            ($dep->isCalculable() && $dep->getDisposalValue() !== null) ? number_format((float) $dep->getDisposalValue(), 2, '.', '') : '-',
            $accumulated,
            $currentValue,
            $asset->notes ?? '',
        ];
    }
}
