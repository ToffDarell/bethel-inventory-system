<?php

namespace App\Exports\Sheets;

use App\Models\Category;
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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class DepreciationCategorySheet implements WithTitle, WithEvents, Export
{
    protected DepreciationService $depreciationService;
    protected Carbon $asOfDate;

    public function __construct(
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
        return 'By Category';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $hospitalName = Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');

                // Header Block Rows 1-3
                $sheet->setCellValue('B1', $hospitalName);
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF0F172A');

                $sheet->setCellValue('B2', 'DEPRECIATION SUMMARY BY CATEGORY');
                $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F172A');

                $metaText = 'As of ' . $this->asOfDate->format('F d, Y') . '   |   Generated ' . now()->format('Y-m-d h:i A');
                $sheet->setCellValue('B3', $metaText);
                $sheet->getStyle('B3')->getFont()->setSize(8.5)->getColor()->setARGB('FF64748B');

                // Crimson line under row 3 across A3:E3
                $sheet->getStyle('A3:E3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB('FF991B1B');

                // Table Headers at Row 5
                $headers = [
                    'A5' => 'Category',
                    'B5' => 'Asset Count',
                    'C5' => 'Total Cost',
                    'D5' => 'Accumulated Dep.',
                    'E5' => 'Current Value',
                ];
                foreach ($headers as $col => $title) {
                    $sheet->setCellValue($col, $title);
                }

                $sheet->getRowDimension(5)->setRowHeight(24);
                $sheet->getStyle('A5:E5')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A5:E5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
                $sheet->getStyle('A5:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

                // Categories Data
                $categories = Category::with('assets')->orderBy('name')->get();
                $cRow = 6;
                $allCount = 0;
                $allCost = 0.0;
                $allAccum = 0.0;
                $allCur = 0.0;

                foreach ($categories as $cat) {
                    $catAssets = $cat->assets->where('status', '!=', 'Disposed');
                    if ($catAssets->isEmpty()) {
                        continue;
                    }

                    $sheet->getRowDimension($cRow)->setRowHeight(20);
                    if ($cRow % 2 === 1) {
                        $sheet->getStyle("A{$cRow}:E{$cRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    }

                    $cCost = 0.0;
                    $cAccum = 0.0;
                    $cCur = 0.0;

                    foreach ($catAssets as $ca) {
                        $dep = $this->depreciationService->calculateForAsset($ca, $this->asOfDate);
                        if ($ca->cost !== null && $ca->cost !== '') {
                            $costF = (float) $ca->cost;
                            $cCost += $costF;
                            if ($dep->isCalculable()) {
                                $cAccum += $dep->getAccumulatedDepreciation();
                                $cCur += $dep->getCurrentValue();
                            } else {
                                $cCur += $costF;
                            }
                        }
                    }

                    $catCount = $catAssets->count();
                    $allCount += $catCount;
                    $allCost += $cCost;
                    $allAccum += $cAccum;
                    $allCur += $cCur;

                    $sheet->setCellValue("A{$cRow}", $cat->name);
                    $sheet->setCellValue("B{$cRow}", $catCount);
                    $sheet->setCellValue("C{$cRow}", $cCost);
                    $sheet->setCellValue("D{$cRow}", $cAccum);
                    $sheet->setCellValue("E{$cRow}", $cCur);

                    $sheet->getStyle("A{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("B{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$cRow}:E{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("C{$cRow}:E{$cRow}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                    $sheet->getStyle("A{$cRow}:E{$cRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');

                    $cRow++;
                }

                $lastCatRow = max(6, $cRow - 1);

                // Totals Row
                $sheet->getRowDimension($cRow)->setRowHeight(22);
                $sheet->setCellValue("A{$cRow}", 'TOTAL');
                $sheet->getStyle("A{$cRow}")->getFont()->setBold(true);

                $sheet->getCell("B{$cRow}")->setValue("=SUM(B6:B{$lastCatRow})");
                $sheet->getCell("B{$cRow}")->setCalculatedValue($allCount);

                $sheet->getCell("C{$cRow}")->setValue("=SUM(C6:C{$lastCatRow})");
                $sheet->getCell("C{$cRow}")->setCalculatedValue($allCost);

                $sheet->getCell("D{$cRow}")->setValue("=SUM(D6:D{$lastCatRow})");
                $sheet->getCell("D{$cRow}")->setCalculatedValue($allAccum);

                $sheet->getCell("E{$cRow}")->setValue("=SUM(E6:E{$lastCatRow})");
                $sheet->getCell("E{$cRow}")->setCalculatedValue($allCur);

                $sheet->getStyle("A{$cRow}:E{$cRow}")->getFont()->setBold(true);
                $sheet->getStyle("B{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$cRow}:E{$cRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("C{$cRow}:E{$cRow}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
                $sheet->getStyle("A{$cRow}:E{$cRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
                $sheet->getStyle("A{$cRow}:E{$cRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FF0F172A');
                $sheet->getStyle("A{$cRow}:E{$cRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB('FF0F172A');

                $sheet->getColumnDimension('A')->setWidth(28);
                $sheet->getColumnDimension('B')->setWidth(14);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(22);
                $sheet->getColumnDimension('E')->setWidth(20);

                // Page setup
                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0);
                $sheet->getHeaderFooter()->setOddFooter('&LBethel Baptist Hospital, Inc. - Estimated values for tracking&RPage &P of &N');
            },
        ];
    }
}
