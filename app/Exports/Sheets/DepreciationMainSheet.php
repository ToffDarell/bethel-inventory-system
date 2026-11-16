<?php

namespace App\Exports\Sheets;

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class DepreciationMainSheet implements WithTitle, WithEvents, Export
{
    protected DepreciationService $depreciationService;
    protected Carbon $asOfDate;

    public function __construct(
        protected ?string $categoryId = null,
        protected ?string $department = null,
        mixed $asOfDate = null,
        protected ?User $user = null,
    ) {
        $this->depreciationService = app(DepreciationService::class);
        if ($asOfDate instanceof Carbon) {
            $this->asOfDate = $asOfDate->copy()->startOfDay();
        } elseif ($asOfDate) {
            try {
                $this->asOfDate = Carbon::parse($asOfDate)->startOfDay();
            } catch (\Throwable) {
                $this->asOfDate = Carbon::today();
            }
        } else {
            $this->asOfDate = Carbon::today();
        }
    }

    public function title(): string
    {
        return 'Depreciation';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. Logo
                $logoPath = public_path('images/bethel-logo.png');
                if (file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('Bethel Hospital Logo');
                    $drawing->setDescription('Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setCoordinates('A1');
                    $drawing->setHeight(50);
                    $drawing->setWorksheet($sheet);
                }

                $hospitalName = Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
                $hospitalAddress = Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
                $username = $this->user?->username ?? 'admin';

                // 2. Header Block Rows 1-4
                $sheet->setCellValue('C1', $hospitalName);
                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF0F172A');

                $sheet->setCellValue('C2', $hospitalAddress);
                $sheet->getStyle('C2')->getFont()->setSize(9)->getColor()->setARGB('FF64748B');

                $sheet->setCellValue('C3', 'DEPRECIATION REPORT');
                $sheet->getStyle('C3')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF0F172A');

                $metaText = 'As of ' . $this->asOfDate->format('F d, Y') . '   |   Prepared by @' . $username . '   |   Generated ' . now()->format('Y-m-d h:i A');
                $sheet->setCellValue('C4', $metaText);
                $sheet->getStyle('C4')->getFont()->setSize(9)->getColor()->setARGB('FF64748B');

                // Crimson line under row 4 across A4:M4
                $sheet->getStyle('A4:M4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF991B1B');

                // 3. Row 5: Policy row & tracking disclaimer (no em dashes)
                $conventionLabel = $this->depreciationService->getConventionLabel();
                $minCost = $this->depreciationService->getMinCost();
                $minCostText = $minCost > 0 ? '₱' . number_format($minCost, 2) : 'None';
                $policyText = 'Policy: Straight-Line Method | ' . $conventionLabel . ' | Minimum Cost to Depreciate: ' . $minCostText . '   |   Estimated values for tracking, not an official accounting record';
                $sheet->setCellValue('A5', $policyText);
                $sheet->getStyle('A5')->getFont()->setItalic(true)->setSize(8.5)->getColor()->setARGB('FF64748B');

                // 4. Row 6: Table Header
                $headers = [
                    'A6' => 'Asset No.',
                    'B6' => 'Asset Description',
                    'C6' => 'Category',
                    'D6' => 'Department',
                    'E6' => 'Year Bought',
                    'F6' => 'Depreciation Start Date',
                    'G6' => 'Months Depreciated',
                    'H6' => 'Useful Life (years)',
                    'I6' => 'Cost',
                    'J6' => 'Disposal Value',
                    'K6' => 'Accumulated Depreciation',
                    'L6' => 'Current Value',
                    'M6' => 'Remarks',
                ];

                foreach ($headers as $cell => $title) {
                    $sheet->setCellValue($cell, $title);
                }

                $sheet->getRowDimension(6)->setRowHeight(24);
                $sheet->getStyle('A6:M6')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A6:M6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
                $sheet->getStyle('A6:M6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

                // 5. Query data
                $query = Asset::query()
                    ->with('category')
                    ->where('status', '!=', 'Disposed')
                    ->orderBy('asset_no');

                if ($this->categoryId !== null && $this->categoryId !== '') {
                    $query->where('category_id', $this->categoryId);
                }

                if ($this->department !== null && $this->department !== '') {
                    $query->where('department', $this->department);
                }

                $assets = $query->get();

                $row = 7;
                $hasZeroCostAssets = false;
                $sumCost = 0.0;
                $sumSalvage = 0.0;
                $sumAccum = 0.0;
                $sumCurrent = 0.0;

                foreach ($assets as $asset) {
                    $dep = $this->depreciationService->calculateForAsset($asset, $this->asOfDate);
                    $sheet->getRowDimension($row)->setRowHeight(20);

                    // Zebra striping
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    }

                    $sheet->setCellValue("A{$row}", $asset->asset_no);
                    $sheet->setCellValue("B{$row}", $asset->asset_name);
                    $sheet->setCellValue("C{$row}", $asset->category?->name ?? 'Uncategorized');
                    $sheet->setCellValue("D{$row}", $asset->department ?? '-');
                    $sheet->setCellValue("E{$row}", $asset->date_acquired ? $asset->date_acquired->format('Y') : '-');
                    $sheet->setCellValue("F{$row}", $asset->date_acquired ? $asset->date_acquired->format('Y-m-d') : '-');
                    $sheet->setCellValue("G{$row}", $dep->isCalculable() ? $dep->getMonthsElapsed() : '-');
                    $lifeYears = $dep->isCalculable() ? $dep->getUsefulLifeYears() : ($asset->category?->default_useful_life_years ?? '-');
                    $sheet->setCellValue("H{$row}", $lifeYears);

                    // Cost
                    if ($asset->cost !== null && $asset->cost !== '') {
                        $cVal = (float) $asset->cost;
                        $sumCost += $cVal;
                        $sheet->setCellValue("I{$row}", $cVal);
                        $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    } else {
                        $sheet->setCellValue("I{$row}", 'No cost entered');
                        $sheet->getStyle("I{$row}")->getFont()->setItalic(true)->getColor()->setARGB('FF94A3B8');
                        $hasZeroCostAssets = true;
                    }

                    // Disposal Value
                    if ($dep->isCalculable() && $dep->getDisposalValue() !== null) {
                        $sVal = (float) $dep->getDisposalValue();
                        $sumSalvage += $sVal;
                        $sheet->setCellValue("J{$row}", $sVal);
                        $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    } else {
                        $sheet->setCellValue("J{$row}", '-');
                        $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // Accumulated Depreciation & Current Value
                    if ($dep->isCalculable()) {
                        $aDep = (float) $dep->getAccumulatedDepreciation();
                        $cVal = (float) $dep->getCurrentValue();
                        $sumAccum += $aDep;
                        $sumCurrent += $cVal;

                        $sheet->setCellValue("K{$row}", $aDep);
                        $sheet->getStyle("K{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');

                        $sheet->setCellValue("L{$row}", $cVal);
                        $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    } elseif ($dep->getMissingReason() === 'Below minimum cost') {
                        $sheet->setCellValue("K{$row}", 'Below min cost');
                        $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("K{$row}")->getFont()->setItalic(true)->getColor()->setARGB('FF94A3B8');

                        if ($asset->cost !== null && $asset->cost !== '') {
                            $cVal = (float) $asset->cost;
                            $sumCurrent += $cVal;
                            $sheet->setCellValue("L{$row}", $cVal);
                            $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                        } else {
                            $sheet->setCellValue("L{$row}", '-');
                            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    } else {
                        $sheet->setCellValue("K{$row}", '-');
                        $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        if ($asset->cost !== null && $asset->cost !== '') {
                            $cVal = (float) $asset->cost;
                            $sumCurrent += $cVal;
                            $sheet->setCellValue("L{$row}", $cVal);
                            $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                        } else {
                            $sheet->setCellValue("L{$row}", '-');
                            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    }

                    $sheet->setCellValue("M{$row}", $asset->notes ?? '');

                    // Alignments
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("E{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("I{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Cell Borders
                    $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');

                    $row++;
                }

                $lastDataRow = max(7, $row - 1);

                // 6. Totals Row
                $sheet->getRowDimension($row)->setRowHeight(22);
                $sheet->setCellValue("A{$row}", 'TOTAL ASSET VALUE');
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FF0F172A');
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getCell("I{$row}")->setValue("=SUM(I7:I{$lastDataRow})");
                $sheet->getCell("I{$row}")->setCalculatedValue($sumCost);
                $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                $sheet->getStyle("I{$row}")->getFont()->setBold(true);

                $sheet->getCell("J{$row}")->setValue("=SUM(J7:J{$lastDataRow})");
                $sheet->getCell("J{$row}")->setCalculatedValue($sumSalvage);
                $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                $sheet->getStyle("J{$row}")->getFont()->setBold(true);

                $sheet->getCell("K{$row}")->setValue("=SUM(K7:K{$lastDataRow})");
                $sheet->getCell("K{$row}")->setCalculatedValue($sumAccum);
                $sheet->getStyle("K{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                $sheet->getStyle("K{$row}")->getFont()->setBold(true);

                $sheet->getCell("L{$row}")->setValue("=SUM(L7:L{$lastDataRow})");
                $sheet->getCell("L{$row}")->setCalculatedValue($sumCurrent);
                $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                $sheet->getStyle("L{$row}")->getFont()->setBold(true);

                $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
                $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF0F172A');
                $sheet->getStyle("A{$row}:M{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB('FF0F172A');

                // 7. Explanatory Note below table
                if ($hasZeroCostAssets) {
                    $noteRow = $row + 1;
                    $sheet->setCellValue("A{$noteRow}", '* Note: Assets marked "No cost entered" are excluded from total cost, accumulated depreciation, and current value sums.');
                    $sheet->getStyle("A{$noteRow}")->getFont()->setItalic(true)->setSize(8.5)->getColor()->setARGB('FF64748B');
                }

                // 8. AutoFilter & Freeze Panes
                $sheet->setAutoFilter("A6:M{$lastDataRow}");
                $sheet->freezePane('A7');

                // 9. Column Widths
                $widths = [
                    'A' => 18, // Asset No
                    'B' => 30, // Description
                    'C' => 22, // Category
                    'D' => 20, // Department
                    'E' => 14, // Year Bought
                    'F' => 22, // Depreciation Start Date
                    'G' => 18, // Months Depreciated
                    'H' => 18, // Useful Life (years)
                    'I' => 18, // Cost
                    'J' => 18, // Salvage Value
                    'K' => 24, // Accumulated Depreciation
                    'L' => 18, // Current Value
                    'M' => 26, // Remarks
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // 10. Print Setup
                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0);
                $pageSetup->setRowsToRepeatAtTopByStartAndEnd(6, 6);
                $sheet->getHeaderFooter()->setOddFooter('&LBethel Baptist Hospital, Inc. - Estimated values for tracking&RPage &P of &N');
                $margins = $sheet->getPageMargins();
                $margins->setTop(0.5);
                $margins->setBottom(0.5);
                $margins->setLeft(0.5);
                $margins->setRight(0.5);
            },
        ];
    }
}
