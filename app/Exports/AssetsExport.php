<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ?string $department = null,
        protected ?string $status = null,
    ) {}

    public function collection(): Collection
    {
        $query = Asset::query()->orderBy('asset_no');

        if ($this->department !== null && $this->department !== '') {
            $query->where('department', $this->department);
        }

        if ($this->status !== null && $this->status !== '') {
            $query->where('status', $this->status);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'asset_no',
            'asset_name',
            'model',
            'serial_no',
            'date_acquired',
            'department',
            'location',
            'status',
            'notes',
        ];
    }

    /**
     * @param  Asset  $asset
     */
    public function map($asset): array
    {
        return [
            $asset->asset_no,
            $asset->asset_name,
            $asset->model,
            $asset->serial_no,
            $asset->date_acquired?->format('Y-m-d'),
            $asset->department,
            $asset->location,
            $asset->status,
            $asset->notes,
        ];
    }
}
