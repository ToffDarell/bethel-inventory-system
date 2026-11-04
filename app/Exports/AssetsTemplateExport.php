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
            'notes',
        ];
    }

    public function array(): array
    {
        return [
            [
                'BBHIND-S300002315',
                'Patient Monitor',
                'Medical Equipment',
                'Mindray uMEC10',
                'SN-123456',
                '2026-01-15',
                'Nursing Station 3',
                'ND-Station 3 Room 39',
                'In Use',
                '120000.00',
                '10',
                '10000.00',
                'Existing paper tag item',
            ],
            [
                '',
                'Wheelchair',
                'Furniture',
                'Standard Folding',
                '',
                '',
                'ER',
                'ER Triage',
                'In Use',
                '',
                '',
                '',
                'Leave asset_no blank to auto-generate',
            ],
        ];
    }
}
