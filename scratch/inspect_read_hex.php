<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Support/Polyfills/XmlWriterPolyfill.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$tempFile = sys_get_temp_dir() . '/test_inspect_zip.xlsx';
// recreate
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$input = "Control\x00Zero\x01One\x02Two\x03Three\x04Four\x05Five\x06Six\x07Seven\x08Eight\x0BVerticalTab\x0CFormFeed\x0EE1\x0FE2\x10E3\x1FE4End";
$sheet->setCellValue('A1', $input);
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save($tempFile);

$reader = IOFactory::createReader('Xlsx');
$readSpreadsheet = $reader->load($tempFile);
$val = $readSpreadsheet->getActiveSheet()->getCell('A1')->getValue();

echo "HEX of read value:\n";
echo bin2hex($val) . "\n";
echo "Readable:\n";
echo addcslashes($val, "\0..\37") . "\n";
@unlink($tempFile);
