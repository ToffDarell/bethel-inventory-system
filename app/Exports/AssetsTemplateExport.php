<?php

namespace App\Exports;

use App\Exports\Sheets\TemplateDataSheet;
use App\Exports\Sheets\TemplateReadMeSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssetsTemplateExport implements WithMultipleSheets, Export
{
    public function __construct(
        protected string $format = 'xlsx',
    ) {}

    public function sheets(): array
    {
        if ($this->format === 'csv') {
            return [
                new TemplateDataSheet(),
            ];
        }

        return [
            new TemplateReadMeSheet(),
            new TemplateDataSheet(),
        ];
    }

    /**
     * Backward-compatible delegation for tests or direct calls
     */
    public function headings(): array
    {
        return (new TemplateDataSheet())->headings();
    }

    public function array(): array
    {
        return (new TemplateDataSheet())->array();
    }
}
