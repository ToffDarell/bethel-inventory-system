<?php

namespace App\Exports;

use App\Exports\Sheets\DepreciationCategorySheet;
use App\Exports\Sheets\DepreciationMainSheet;
use App\Exports\Sheets\DepreciationRawCsvSheet;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DepreciationExport implements WithMultipleSheets, Export
{
    public function __construct(
        protected ?string $categoryId = null,
        protected ?string $department = null,
        protected mixed $asOfDate = null,
        protected string $format = 'xlsx',
        protected ?User $user = null,
    ) {
        if (! $this->user && auth()->check()) {
            $this->user = auth()->user();
        }
    }

    public function sheets(): array
    {
        if ($this->format === 'csv') {
            return [
                new DepreciationRawCsvSheet($this->categoryId, $this->department, $this->asOfDate),
            ];
        }

        return [
            new DepreciationMainSheet($this->categoryId, $this->department, $this->asOfDate, $this->user),
            new DepreciationCategorySheet($this->asOfDate, $this->user),
        ];
    }

    /**
     * Backward-compatible delegation for tests or direct calls
     */
    public function collection(): Collection
    {
        return (new DepreciationRawCsvSheet($this->categoryId, $this->department, $this->asOfDate))->collection();
    }

    public function headings(): array
    {
        return (new DepreciationRawCsvSheet($this->categoryId, $this->department, $this->asOfDate))->headings();
    }

    public function map($asset): array
    {
        return (new DepreciationRawCsvSheet($this->categoryId, $this->department, $this->asOfDate))->map($asset);
    }
}
