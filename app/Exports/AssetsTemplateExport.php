<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssetsTemplateExport implements FromArray, WithHeadings
{
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

    public function array(): array
    {
        return [
            [
                'BBHIND-S300002315',
                'Patient Monitor',
                'Mindray uMEC10',
                'SN-123456',
                '2026-01-15',
                'Nursing Station 3',
                'ND-Station 3 Room 39',
                'In Use',
                'Existing paper tag item',
            ],
            [
                '',
                'Wheelchair',
                'Standard Folding',
                '',
                '',
                'ER',
                'ER Triage',
                'In Use',
                'Leave asset_no blank to auto-generate',
            ],
        ];
    }
}
