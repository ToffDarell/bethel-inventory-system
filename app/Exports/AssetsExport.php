<?php

namespace App\Exports;

use App\Models\Asset;
use App\Models\User;
use App\Services\DepreciationService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssetsExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected ?string $department = null,
        protected ?string $status = null,
        protected ?User $user = null,
    ) {}

    protected function isAdmin(): bool
    {
        return $this->user?->isAdmin() ?? false;
    }

    public function headings(): array
    {
        if ($this->isAdmin()) {
            return [
                'asset_no',
                'asset_name',
                'category',
                'model',
                'serial_no',
                'date_acquired',
                'department',
                'location',
                'status',
                'cost',
                'useful_life_years',
                'salvage_value',
                'accumulated_depreciation',
                'current_value',
                'notes',
            ];
        }

        return [
            'asset_no',
            'asset_name',
            'category',
            'model',
            'serial_no',
            'date_acquired',
            'department',
            'location',
            'status',
            'notes',
        ];
    }

    public function array(): array
    {
        $query = Asset::query()
            ->with('category')
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
            $categoryName = $asset->category?->name ?? '';
            $dateStr = $asset->date_acquired?->format('Y-m-d') ?? '';

            if ($isAdmin) {
                $dep = $depService->calculateForAsset($asset);

                $costVal = $asset->cost !== null ? (float) $asset->cost : null;
                $salvageVal = $asset->salvage_value !== null ? (float) $asset->salvage_value : null;

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
                    $categoryName,
                    $asset->model ?? '',
                    $asset->serial_no ?? '',
                    $dateStr,
                    $asset->department ?? '',
                    $asset->location ?? '',
                    $asset->status,
                    $costFormatted,
                    $asset->useful_life_years !== null ? (string) $asset->useful_life_years : '',
                    $salvageFormatted,
                    $accumFormatted,
                    $currentFormatted,
                    $asset->notes ?? '',
                ];
            } else {
                $rows[] = [
                    $asset->asset_no,
                    $asset->asset_name,
                    $categoryName,
                    $asset->model ?? '',
                    $asset->serial_no ?? '',
                    $dateStr,
                    $asset->department ?? '',
                    $asset->location ?? '',
                    $asset->status,
                    $asset->notes ?? '',
                ];
            }
        }

        // Include "Total asset value" and depreciation totals row for admins only
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
                '₱' . number_format($totalCost, 2),
                '',
                '₱' . number_format($totalSalvage, 2),
                '₱' . number_format($totalAccumulated, 2),
                '₱' . number_format($totalCurrentValue, 2),
                '',
            ];
        }

        return $rows;
    }
}
