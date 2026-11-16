<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TemplateDataSheet implements FromArray, WithHeadings, WithTitle, WithEvents, Export
{
    public function title(): string
    {
        return 'Assets Template';
    }

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
            'disposal_value',
            'remarks',
            'vendor',
            'asset_template',
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
                '3 J MEDICAL',
                'Patient Monitor',
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
                'DONATION',
                '',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style Header Row 1
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getStyle('A1:R1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1:R1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
                $sheet->getStyle('A1:R1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Sample Data Rows (Row 2 and 3)
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getStyle('A2:R3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');

                // Column Widths
                $widths = [
                    'A' => 22, // asset_no
                    'B' => 26, // asset_description
                    'C' => 16, // brand
                    'D' => 16, // model
                    'E' => 18, // serial_number
                    'F' => 18, // department
                    'G' => 16, // section
                    'H' => 20, // assigned_personnel
                    'I' => 20, // category
                    'J' => 14, // year_bought
                    'K' => 18, // asset_condition
                    'L' => 14, // status
                    'M' => 16, // cost
                    'N' => 18, // useful_life_years
                    'O' => 16, // salvage_value
                    'P' => 28, // remarks
                    'Q' => 22, // vendor
                    'R' => 22, // asset_template
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                $sheet->freezePane('A2');
            },
        ];
    }
}
