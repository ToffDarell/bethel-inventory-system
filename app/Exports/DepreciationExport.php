<?php

namespace App\Exports;

use App\Models\Asset;
use App\Services\DepreciationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepreciationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected DepreciationService $depreciationService;

    public function __construct(
        protected ?string $categoryId = null,
        protected ?string $department = null,
        protected mixed $asOfDate = null,
    ) {
        $this->depreciationService = app(DepreciationService::class);
    }

    public function collection(): Collection
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

        return $query->get();
    }

    public function headings(): array
    {
        $conventionLabel = $this->depreciationService->getConventionLabel();
        $minCost = $this->depreciationService->getMinCost();
        $minCostText = $minCost > 0 ? 'PHP ' . number_format($minCost, 2) : 'None (all assets)';

        return [
            ['DISCLAIMER: Estimated values for tracking, not an official accounting record'],
            ['POLICY: ' . $conventionLabel . ' | Minimum Cost to Depreciate: ' . $minCostText],
            [],
            [
                'Asset No.',
                'Asset Description',
                'Category',
                'Year Bought',
                'Useful Life (Years)',
                'Cost (PHP)',
                'Salvage (PHP)',
                'Accumulated Depreciation (PHP)',
                'Current Value (PHP)',
            ],
        ];
    }

    /**
     * @param  Asset  $asset
     */
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
            $asset->date_acquired ? $asset->date_acquired->format('Y') : '',
            $asset->useful_life_years ?? '',
            $asset->cost !== null ? number_format((float) $asset->cost, 2, '.', '') : '',
            $asset->salvage_value !== null ? number_format((float) $asset->salvage_value, 2, '.', '') : '-',
            $accumulated,
            $currentValue,
        ];
    }
}
