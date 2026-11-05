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
            'asset_description',
            'brand',
            'model',
            'serial_number',
            'department',
            'section',
            'assigned_personnel',
            'category',
            'year_bought',
            'asset_condition',
            'status',
            'cost',
            'useful_life_years',
            'salvage_value',
            'remarks',
        ];
    }

    public function array(): array
    {
        return [
            [
                'BBHIND-S300002315',
                'Patient Monitor',
                'Mindray',
                'uMEC10',
                'SN-123456',
                'Nursing',
                'Station 3',
                'Nurse Joy',
                'Medical Equipment',
                '2023',
                'In Good Condition',
                'In Use',
                '120000.00',
                '10',
                '10000.00',
                'Existing paper tag item',
            ],
            [
                '',
                'Wheelchair',
                'Drive Medical',
                'Standard Folding',
                '',
                'Emergency Room',
                'Triage',
                '',
                'Furniture',
                '2024',
                'Brand-new',
                'In Use',
                '',
                '',
                '',
                'Leave asset_no blank to auto-generate',
            ],
        ];
    }
}
