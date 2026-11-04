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
        return [
            'Asset No.',
            'Asset Name',
            'Category',
            'Date Acquired',
            'Useful Life (Years)',
            'Cost (PHP)',
            'Salvage Value (PHP)',
            'Accumulated Depreciation (PHP)',
            'Current Value (PHP)',
        ];
    }

    /**
     * @param  Asset  $asset
     */
    public function map($asset): array
    {
        $dep = $this->depreciationService->calculateForAsset($asset);

        return [
            $asset->asset_no,
            $asset->asset_name,
            $asset->category?->name ?? 'Uncategorized',
            $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : '',
            $asset->useful_life_years ?? '',
            $asset->cost !== null ? number_format((float) $asset->cost, 2, '.', '') : '',
            $asset->salvage_value !== null ? number_format((float) $asset->salvage_value, 2, '.', '') : '',
            $dep->isCalculable() ? number_format((float) $dep->getAccumulatedDepreciation(), 2, '.', '') : '',
            $dep->isCalculable() ? number_format((float) $dep->getCurrentValue(), 2, '.', '') : ($asset->cost !== null ? number_format((float) $asset->cost, 2, '.', '') : ''),
        ];
    }
}
