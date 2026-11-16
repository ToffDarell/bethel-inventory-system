<?php

namespace App\Exports\Sheets;

use App\Models\Setting;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TemplateReadMeSheet implements WithTitle, WithEvents, Export
{
    public function title(): string
    {
        return 'Read Me';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $hospitalName = Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');

                // Header
                $sheet->setCellValue('A1', $hospitalName);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF0F172A');

                $sheet->setCellValue('A2', 'ASSET IMPORT SPREADSHEET GUIDE');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F172A');

                $sheet->setCellValue('A3', 'Please review the column rules below. Fill your data into the next tab: "Assets Template".');
                $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setARGB('FF64748B');

                // Crimson line
                $sheet->getStyle('A3:D3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF991B1B');

                // Table Header at Row 5
                $headers = [
                    'A5' => 'Column Name',
                    'B5' => 'Required / Optional',
                    'C5' => 'Allowed Values / Format',
                    'D5' => 'Description & Examples',
                ];
                foreach ($headers as $cell => $title) {
                    $sheet->setCellValue($cell, $title);
                }

                $sheet->getRowDimension(5)->setRowHeight(24);
                $sheet->getStyle('A5:D5')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A5:D5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
                $sheet->getStyle('A5:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $guidelines = [
                    ['asset_no', 'Optional', 'e.g. BBH-000101', 'Leave blank to auto-generate the next available hospital asset number.'],
                    ['asset_description', 'Required', 'Text', 'Equipment name or description (e.g. Patient Monitor, Wheelchair).'],
                    ['brand', 'Optional', 'Text', 'Manufacturer or brand name (e.g. Mindray, GE, Steris).'],
                    ['model', 'Optional', 'Text', 'Model name or number (e.g. uMEC10, LifePak 15).'],
                    ['serial_number', 'Optional', 'Text', 'Manufacturer serial number from equipment plate.'],
                    ['department', 'Optional', 'Text', 'Hospital department (e.g. Nursing, Emergency Room, Laboratory, Radiology).'],
                    ['section', 'Optional', 'Text', 'Specific area or station (e.g. Station 3, ICU Bay 4, Triage).'],
                    ['assigned_personnel', 'Optional', 'Text', 'Staff member or clinician responsible for the asset.'],
                    ['category', 'Optional', 'Text', 'Asset category (e.g. Medical Equipment, IT Equipment, Furniture, Vehicle).'],
                    ['year_bought', 'Optional', 'YYYY or YYYY-MM-DD', 'Acquisition year (e.g. 2023) or full date (e.g. 2023-05-15).'],
                    ['asset_condition', 'Optional', 'Brand-new, Refurbish, Damage, In Good Condition', 'Physical condition of equipment. Default is In Good Condition.'],
                    ['status', 'Optional', 'In Use, Under Repair, Stored, Disposed', 'Operational status. Default is In Use.'],
                    ['cost', 'Optional', 'Number (e.g. 120000.00)', 'Acquisition cost in PHP. Enter digits only, no currency signs or commas.'],
                    ['useful_life_years', 'Optional', 'Whole number (e.g. 10)', 'Expected useful life in years for straight-line depreciation.'],
                    ['disposal_value', 'Optional', 'Number (e.g. 10000.00)', 'Estimated residual or disposal value at end of useful life (legacy header "salvage_value" is also accepted).'],
                    ['remarks', 'Optional', 'Text', 'General notes or maintenance history.'],
                    ['vendor', 'Optional', 'Text', 'Supplier, store, or donation source (e.g. 3 J MEDICAL, CITI HARDWARE, DONATION).'],
                    ['asset_template', 'Optional', 'Text', 'Equipment template name from Asset Masterlist; fills blank description, brand, model, category, and remarks.'],
                ];

                $row = 6;
                foreach ($guidelines as $item) {
                    $sheet->getRowDimension($row)->setRowHeight(20);
                    if ($row % 2 === 1) {
                        $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    }

                    $sheet->setCellValue("A{$row}", $item[0]);
                    $sheet->setCellValue("B{$row}", $item[1]);
                    $sheet->setCellValue("C{$row}", $item[2]);
                    $sheet->setCellValue("D{$row}", $item[3]);

                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    if ($item[1] === 'Required') {
                        $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FFDC2626');
                        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                    } else {
                        $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF64748B');
                    }

                    $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');

                    $row++;
                }

                $sheet->getColumnDimension('A')->setWidth(24);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(38);
                $sheet->getColumnDimension('D')->setWidth(56);
            },
        ];
    }
}
