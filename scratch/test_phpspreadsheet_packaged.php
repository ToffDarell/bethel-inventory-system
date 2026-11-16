<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Support/Polyfills/XmlWriterPolyfill.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Asset with special chars: Defibrillator & EKG ñ');
$sheet->setCellValue('B1', 12345.67);

$writer = new Xlsx($spreadsheet);
$tempFile = sys_get_temp_dir() . '/test_packaged_export.xlsx';
$writer->save($tempFile);

echo "FILE_EXISTS: " . (file_exists($tempFile) ? "YES" : "NO") . "\n";
echo "FILE_SIZE: " . filesize($tempFile) . " bytes\n";
@unlink($tempFile);
