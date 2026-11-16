<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssetTemplatesSampleExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Asset Templates';
    }

    public function headings(): array
    {
        return [
            'name',
            'brand',
            'model',
            'category',
            'default_remarks',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Infusion Pump',
                'B. Braun',
                'Infusomat Space',
                'Medical Equipment',
                'Standard IV infusion pump with pole clamp',
            ],
            [
                'Office Swivel Chair',
                'Local',
                'Mesh High-Back',
                'Furniture',
                '',
            ],
            [
                'Barcode Label Printer',
                'Zebra',
                'ZD220',
                'IT Equipment',
                'Thermal transfer property tag printer',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
                $sheet->getStyle('A1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A2:E4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');

                $sheet->getColumnDimension('A')->setWidth(28);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(22);
                $sheet->getColumnDimension('E')->setWidth(40);
                $sheet->freezePane('A2');
            },
        ];
    }
}
