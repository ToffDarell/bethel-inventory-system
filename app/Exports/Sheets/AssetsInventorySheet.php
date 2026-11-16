<?php

namespace App\Exports\Sheets;

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use App\Services\DepreciationService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AssetsInventorySheet implements WithTitle, WithEvents, Export
{
    protected DepreciationService $depreciationService;

    public function __construct(
        protected ?string $department = null,
        protected ?string $status = null,
        protected ?User $user = null,
    ) {
        $this->depreciationService = app(DepreciationService::class);
    }

    public function title(): string
    {
        return 'Asset Inventory';
    }

    protected function isAdmin(): bool
    {
        return $this->user?->isAdmin() ?? false;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $isAdmin = $this->isAdmin();

                // 1. Hospital Logo
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
                $username = $this->user?->username ?? 'staff';

                // 2. Header Block Rows 1-4
                $sheet->setCellValue('C1', $hospitalName);
                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF0F172A');

                $sheet->setCellValue('C2', $hospitalAddress);
                $sheet->getStyle('C2')->getFont()->setSize(9)->getColor()->setARGB('FF64748B');

                $sheet->setCellValue('C3', 'ASSET INVENTORY REPORT');
                $sheet->getStyle('C3')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF0F172A');

                $deptFilter = $this->department ? $this->department : 'All Departments';
                $statusFilter = $this->status ? $this->status : 'All Statuses';
                $metaText = 'Filter: ' . $deptFilter . ' / ' . $statusFilter . '   |   Prepared by @' . $username . '   |   Generated ' . now()->format('Y-m-d h:i A');
                $sheet->setCellValue('C4', $metaText);
                $sheet->getStyle('C4')->getFont()->setSize(9)->getColor()->setARGB('FF64748B');

                // Determine end column: T for Admin (20 cols), O for Staff (15 cols)
                $endCol = $isAdmin ? 'T' : 'O';

                // Crimson line under row 4
                $sheet->getStyle("A4:{$endCol}4")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF991B1B');

                // 3. Row 5: Policy / Tracking Disclaimer (no em dashes)
                if ($isAdmin) {
                    $conventionLabel = $this->depreciationService->getConventionLabel();
                    $minCost = $this->depreciationService->getMinCost();
                    $minCostText = $minCost > 0 ? '₱' . number_format($minCost, 2) : 'None';
                    $policyText = 'Policy: Straight-Line Method | ' . $conventionLabel . ' | Minimum Cost to Depreciate: ' . $minCostText . '   |   Estimated values for tracking, not an official accounting record';
                } else {
                    $policyText = 'Estimated values for tracking, not an official accounting record';
                }
                $sheet->setCellValue('A5', $policyText);
                $sheet->getStyle('A5')->getFont()->setItalic(true)->setSize(8.5)->getColor()->setARGB('FF64748B');

                // 4. Row 6: Table Headers
                if ($isAdmin) {
                    $headers = [
                        'A6' => 'Asset No.',
                        'B6' => 'Asset Description',
                        'C6' => 'Brand',
                        'D6' => 'Model',
                        'E6' => 'Serial Number',
                        'F6' => 'Department',
                        'G6' => 'Section',
                        'H6' => 'Location',
                        'I6' => 'Assigned Personnel',
                        'J6' => 'Category',
                        'K6' => 'Year Bought',
                        'L6' => 'Asset Condition',
                        'M6' => 'Status',
                        'N6' => 'Cost',
                        'O6' => 'Useful Life (years)',
                        'P6' => 'Salvage Value',
                        'Q6' => 'Accumulated Depreciation',
                        'R6' => 'Current Value',
                        'S6' => 'Remarks',
                        'T6' => 'Vendor',
                    ];
                } else {
                    $headers = [
                        'A6' => 'Asset No.',
                        'B6' => 'Asset Description',
                        'C6' => 'Brand',
                        'D6' => 'Model',
                        'E6' => 'Serial Number',
                        'F6' => 'Department',
                        'G6' => 'Section',
                        'H6' => 'Location',
                        'I6' => 'Assigned Personnel',
                        'J6' => 'Category',
                        'K6' => 'Year Bought',
                        'L6' => 'Asset Condition',
                        'M6' => 'Status',
                        'N6' => 'Remarks',
                        'O6' => 'Vendor',
                    ];
                }

                foreach ($headers as $cell => $title) {
                    $sheet->setCellValue($cell, $title);
                }

                $sheet->getRowDimension(6)->setRowHeight(24);
                $sheet->getStyle("A6:{$endCol}6")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle("A6:{$endCol}6")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
                $sheet->getStyle("A6:{$endCol}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

                // 5. Query Data
                $query = Asset::query()
                    ->with(['category', 'vendor'])
                    ->orderBy('asset_no');

                if ($this->department !== null && $this->department !== '') {
                    $query->where('department', $this->department);
                }

                if ($this->status !== null && $this->status !== '') {
                    $query->where('status', $this->status);
                }

                $assets = $query->get();
                $row = 7;
                $hasZeroCostAssets = false;
                $sumCost = 0.0;
                $sumSalvage = 0.0;
                $sumAccum = 0.0;
                $sumCurrent = 0.0;

                foreach ($assets as $asset) {
                    $sheet->getRowDimension($row)->setRowHeight(20);

                    // Zebra striping
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$endCol}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    }

                    $sheet->setCellValue("A{$row}", $asset->asset_no);
                    $sheet->setCellValue("B{$row}", $asset->asset_name);
                    $sheet->setCellValue("C{$row}", $asset->brand ?? '-');
                    $sheet->setCellValue("D{$row}", $asset->model ?? '-');
                    $sheet->setCellValue("E{$row}", $asset->serial_no ?? '-');
                    $sheet->setCellValue("F{$row}", $asset->department ?? '-');
                    $sheet->setCellValue("G{$row}", $asset->section ?? '-');
                    $sheet->setCellValue("H{$row}", $asset->location ?? '-');
                    $sheet->setCellValue("I{$row}", $asset->assigned_personnel ?? '-');
                    $sheet->setCellValue("J{$row}", $asset->category?->name ?? 'Uncategorized');
                    $sheet->setCellValue("K{$row}", $asset->date_acquired ? $asset->date_acquired->format('Y') : '-');
                    $sheet->setCellValue("L{$row}", $asset->asset_condition ?? 'In Good Condition');
                    $sheet->setCellValue("M{$row}", $asset->status);

                    if ($isAdmin) {
                        $dep = $this->depreciationService->calculateForAsset($asset);

                        // Cost
                        if ($asset->cost !== null && $asset->cost !== '') {
                            $cVal = (float) $asset->cost;
                            $sumCost += $cVal;
                            $sheet->setCellValue("N{$row}", $cVal);
                            $sheet->getStyle("N{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                        } else {
                            $sheet->setCellValue("N{$row}", 'No cost entered');
                            $sheet->getStyle("N{$row}")->getFont()->setItalic(true)->getColor()->setARGB('FF94A3B8');
                            $hasZeroCostAssets = true;
                        }

                        // Useful life
                        $lifeVal = $dep->isCalculable()
                            ? $dep->getUsefulLifeYears()
                            : ($asset->useful_life_years ?? $asset->category?->default_useful_life_years ?? '-');
                        $sheet->setCellValue("O{$row}", $lifeVal);

                        // Disposal Value
                        if ($dep->isCalculable() && $dep->getDisposalValue() !== null) {
                            $sVal = (float) $dep->getDisposalValue();
                            $sumSalvage += $sVal;
                            $sheet->setCellValue("P{$row}", $sVal);
                            $sheet->getStyle("P{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                        } elseif ($asset->salvage_value !== null && $asset->salvage_value !== '') {
                            $sVal = (float) $asset->salvage_value;
                            $sumSalvage += $sVal;
                            $sheet->setCellValue("P{$row}", $sVal);
                            $sheet->getStyle("P{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                        } else {
                            $sheet->setCellValue("P{$row}", '-');
                            $sheet->getStyle("P{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }

                        // Accumulated & Current Value
                        if ($dep->isCalculable()) {
                            $aDep = (float) $dep->getAccumulatedDepreciation();
                            $cVal = (float) $dep->getCurrentValue();
                            $sumAccum += $aDep;
                            $sumCurrent += $cVal;

                            $sheet->setCellValue("Q{$row}", $aDep);
                            $sheet->getStyle("Q{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');

                            $sheet->setCellValue("R{$row}", $cVal);
                            $sheet->getStyle("R{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                        } elseif ($dep->getMissingReason() === 'Below minimum cost') {
                            $sheet->setCellValue("Q{$row}", 'Below min cost');
                            $sheet->getStyle("Q{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("Q{$row}")->getFont()->setItalic(true)->getColor()->setARGB('FF94A3B8');

                            if ($asset->cost !== null && $asset->cost !== '') {
                                $cVal = (float) $asset->cost;
                                $sumCurrent += $cVal;
                                $sheet->setCellValue("R{$row}", $cVal);
                                $sheet->getStyle("R{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                            } else {
                                $sheet->setCellValue("R{$row}", '-');
                                $sheet->getStyle("R{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            }
                        } else {
                            $sheet->setCellValue("Q{$row}", '-');
                            $sheet->getStyle("Q{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                            if ($asset->cost !== null && $asset->cost !== '') {
                                $cVal = (float) $asset->cost;
                                $sumCurrent += $cVal;
                                $sheet->setCellValue("R{$row}", $cVal);
                                $sheet->getStyle("R{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                            } else {
                                $sheet->setCellValue("R{$row}", '-');
                                $sheet->getStyle("R{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            }
                        }

                        $sheet->setCellValue("S{$row}", $asset->notes ?? '');
                        $sheet->setCellValue("T{$row}", $asset->vendor?->name ?? '');

                        // Alignments
                        $sheet->getStyle("A{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle("K{$row}:M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("P{$row}:R{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("S{$row}:T{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    } else {
                        // Staff view: column N is remarks, column O is vendor
                        $sheet->setCellValue("N{$row}", $asset->notes ?? '');
                        $sheet->setCellValue("O{$row}", $asset->vendor?->name ?? '');

                        $sheet->getStyle("A{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle("K{$row}:M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("N{$row}:O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }

                    // Borders
                    $sheet->getStyle("A{$row}:{$endCol}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');

                    $row++;
                }

                $lastDataRow = max(7, $row - 1);

                // 6. Totals Row (Admins only)
                if ($isAdmin) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
                    $sheet->setCellValue("A{$row}", 'TOTAL ASSET VALUE');
                    $sheet->mergeCells("A{$row}:M{$row}");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FF0F172A');
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

                    $sheet->getCell("N{$row}")->setValue("=SUM(N7:N{$lastDataRow})");
                    $sheet->getCell("N{$row}")->setCalculatedValue($sumCost);
                    $sheet->getStyle("N{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    $sheet->getStyle("N{$row}")->getFont()->setBold(true);

                    $sheet->setCellValue("O{$row}", '-');
                    $sheet->getStyle("O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getCell("P{$row}")->setValue("=SUM(P7:P{$lastDataRow})");
                    $sheet->getCell("P{$row}")->setCalculatedValue($sumSalvage);
                    $sheet->getStyle("P{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    $sheet->getStyle("P{$row}")->getFont()->setBold(true);

                    $sheet->getCell("Q{$row}")->setValue("=SUM(Q7:Q{$lastDataRow})");
                    $sheet->getCell("Q{$row}")->setCalculatedValue($sumAccum);
                    $sheet->getStyle("Q{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    $sheet->getStyle("Q{$row}")->getFont()->setBold(true);

                    $sheet->getCell("R{$row}")->setValue("=SUM(R7:R{$lastDataRow})");
                    $sheet->getCell("R{$row}")->setCalculatedValue($sumCurrent);
                    $sheet->getStyle("R{$row}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    $sheet->getStyle("R{$row}")->getFont()->setBold(true);

                    $sheet->setCellValue("S{$row}", '');
                    $sheet->setCellValue("T{$row}", '');

                    $sheet->getStyle("A{$row}:T{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
                    $sheet->getStyle("A{$row}:T{$row}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF0F172A');
                    $sheet->getStyle("A{$row}:T{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB('FF0F172A');

                    if ($hasZeroCostAssets) {
                        $noteRow = $row + 1;
                        $sheet->setCellValue("A{$noteRow}", '* Note: Assets marked "No cost entered" are excluded from total cost, accumulated depreciation, and current value sums.');
                        $sheet->getStyle("A{$noteRow}")->getFont()->setItalic(true)->setSize(8.5)->getColor()->setARGB('FF64748B');
                    }
                }

                // 7. AutoFilter & Freeze Panes
                $sheet->setAutoFilter("A6:{$endCol}{$lastDataRow}");
                $sheet->freezePane('A7');

                // 8. Column Widths
                if ($isAdmin) {
                    $widths = [
                        'A' => 18, // Asset No.
                        'B' => 28, // Asset Description
                        'C' => 16, // Brand
                        'D' => 16, // Model
                        'E' => 18, // Serial Number
                        'F' => 18, // Department
                        'G' => 16, // Section
                        'H' => 16, // Location
                        'I' => 20, // Assigned Personnel
                        'J' => 20, // Category
                        'K' => 14, // Year Bought
                        'L' => 18, // Asset Condition
                        'M' => 14, // Status
                        'N' => 18, // Cost
                        'O' => 16, // Useful Life (years)
                        'P' => 18, // Salvage Value
                        'Q' => 24, // Accumulated Depreciation
                        'R' => 18, // Current Value
                        'S' => 24, // Remarks
                        'T' => 22, // Vendor
                    ];
                } else {
                    $widths = [
                        'A' => 18, // Asset No.
                        'B' => 28, // Asset Description
                        'C' => 16, // Brand
                        'D' => 16, // Model
                        'E' => 18, // Serial Number
                        'F' => 18, // Department
                        'G' => 16, // Section
                        'H' => 16, // Location
                        'I' => 20, // Assigned Personnel
                        'J' => 20, // Category
                        'K' => 14, // Year Bought
                        'L' => 18, // Asset Condition
                        'M' => 14, // Status
                        'N' => 24, // Remarks
                        'O' => 22, // Vendor
                    ];
                }

                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // 9. Print Setup
                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0);
                $pageSetup->setRowsToRepeatAtTopByStartAndEnd(6, 6);
                $sheet->getHeaderFooter()->setOddFooter('&LBethel Baptist Hospital, Inc. - Asset Inventory&RPage &P of &N');
                $margins = $sheet->getPageMargins();
                $margins->setTop(0.5);
                $margins->setBottom(0.5);
                $margins->setLeft(0.5);
                $margins->setRight(0.5);
            },
        ];
    }
}
