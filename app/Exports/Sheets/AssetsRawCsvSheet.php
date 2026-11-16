<?php

namespace App\Exports\Sheets;

use App\Models\Asset;
use App\Models\User;
use App\Services\DepreciationService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AssetsRawCsvSheet implements FromArray, WithHeadings, WithTitle, Export
{
    public function __construct(
        protected ?string $department = null,
        protected ?string $status = null,
        protected ?User $user = null,
    ) {}

    public function title(): string
    {
        return 'Assets';
    }

    protected function isAdmin(): bool
    {
        return $this->user?->isAdmin() ?? false;
    }

    public function headings(): array
    {
        if ($this->isAdmin()) {
            return [
                'asset_no',
                'asset_description',
                'brand',
                'model',
                'serial_number',
                'department',
                'section',
                'location',
                'assigned_personnel',
                'category',
                'year_bought',
                'asset_condition',
                'status',
                'cost',
                'useful_life_years',
                'salvage_value',
                'accumulated_depreciation',
                'current_value',
                'remarks',
                'vendor',
            ];
        }

        return [
            'asset_no',
            'asset_description',
            'brand',
            'model',
            'serial_number',
            'department',
            'section',
            'location',
            'assigned_personnel',
            'category',
            'year_bought',
            'asset_condition',
            'status',
            'remarks',
            'vendor',
        ];
    }

    public function array(): array
    {
        $query = Asset::query()
            ->with(['category', 'vendor'])
            ->orderBy('asset_no');

        if ($this->department !== null && $this->department !== '') {
            $query->where('department', $this->department);
        }

        if ($this->status !== null && $this->status !== '') {
            $query->where('status', $this->status);
        }

        $assets = $query->get();
        $rows = [];
        $isAdmin = $this->isAdmin();
        $depService = app(DepreciationService::class);

        $totalCost = 0.0;
        $totalSalvage = 0.0;
        $totalAccumulated = 0.0;
        $totalCurrentValue = 0.0;

        foreach ($assets as $asset) {
            $categoryName = $asset->category?->name ?? 'Uncategorized';
            $yearBought = $asset->date_acquired?->format('Y') ?? '';
            $condition = $asset->asset_condition ?? 'Not set';

            if ($isAdmin) {
                $dep = $depService->calculateForAsset($asset);

                $costVal = $asset->cost !== null ? (float) $asset->cost : null;
                $salvageVal = ($dep->isCalculable() && $dep->getDisposalValue() !== null)
                    ? (float) $dep->getDisposalValue()
                    : ($asset->salvage_value !== null ? (float) $asset->salvage_value : null);

                $usefulLifeVal = $dep->isCalculable()
                    ? (string) $dep->getUsefulLifeYears()
                    : ($asset->useful_life_years !== null ? (string) $asset->useful_life_years : ($asset->category?->default_useful_life_years !== null ? (string) $asset->category->default_useful_life_years : ''));

                if ($costVal !== null) {
                    $totalCost += $costVal;
                }
                if ($salvageVal !== null) {
                    $totalSalvage += $salvageVal;
                }

                if ($dep->isCalculable()) {
                    $totalAccumulated += $dep->getAccumulatedDepreciation();
                    $totalCurrentValue += $dep->getCurrentValue();
                } elseif ($costVal !== null) {
                    $totalCurrentValue += $costVal;
                }

                $costFormatted = $costVal !== null ? '₱' . number_format($costVal, 2) : 'No cost entered';
                $salvageFormatted = $salvageVal !== null ? '₱' . number_format($salvageVal, 2) : '';
                $accumFormatted = $dep->isCalculable() ? '₱' . number_format($dep->getAccumulatedDepreciation(), 2) : '';
                $currentFormatted = $dep->isCalculable()
                    ? '₱' . number_format($dep->getCurrentValue(), 2)
                    : ($costVal !== null ? '₱' . number_format($costVal, 2) : '');

                $rows[] = [
                    $asset->asset_no,
                    $asset->asset_name,
                    $asset->brand ?? '',
                    $asset->model ?? '',
                    $asset->serial_no ?? '',
                    $asset->department ?? '',
                    $asset->section ?? '',
                    $asset->location ?? '',
                    $asset->assigned_personnel ?? '',
                    $categoryName,
                    $yearBought,
                    $condition,
                    $asset->status,
                    $costFormatted,
                    $usefulLifeVal,
                    $salvageFormatted,
                    $accumFormatted,
                    $currentFormatted,
                    $asset->notes ?? '',
                    $asset->vendor?->name ?? '',
                ];
            } else {
                $rows[] = [
                    $asset->asset_no,
                    $asset->asset_name,
                    $asset->brand ?? '',
                    $asset->model ?? '',
                    $asset->serial_no ?? '',
                    $asset->department ?? '',
                    $asset->section ?? '',
                    $asset->location ?? '',
                    $asset->assigned_personnel ?? '',
                    $categoryName,
                    $yearBought,
                    $condition,
                    $asset->status,
                    $asset->notes ?? '',
                    $asset->vendor?->name ?? '',
                ];
            }
        }

        if ($isAdmin && count($rows) > 0) {
            $rows[] = [
                'Total asset value',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '₱' . number_format($totalCost, 2),
                '',
                '₱' . number_format($totalSalvage, 2),
                '₱' . number_format($totalAccumulated, 2),
                '₱' . number_format($totalCurrentValue, 2),
                '',
                '',
            ];
        }

        return $rows;
    }
}
