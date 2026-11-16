<?php

namespace App\Exports;

use App\Exports\Sheets\AssetsInventorySheet;
use App\Exports\Sheets\AssetsRawCsvSheet;
use App\Models\User;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssetsExport implements WithMultipleSheets, Export
{
    public function __construct(
        protected ?string $department = null,
        protected ?string $status = null,
        protected ?User $user = null,
        protected string $format = 'xlsx',
    ) {
        if (! $this->user && auth()->check()) {
            $this->user = auth()->user();
        }
    }

    public function sheets(): array
    {
        if ($this->format === 'csv') {
            return [
                new AssetsRawCsvSheet($this->department, $this->status, $this->user),
            ];
        }

        return [
            new AssetsInventorySheet($this->department, $this->status, $this->user),
        ];
    }

    /**
     * Backward-compatible delegation for tests or direct calls
     */
    public function headings(): array
    {
        return (new AssetsRawCsvSheet($this->department, $this->status, $this->user))->headings();
    }

    public function array(): array
    {
        return (new AssetsRawCsvSheet($this->department, $this->status, $this->user))->array();
    }
}
