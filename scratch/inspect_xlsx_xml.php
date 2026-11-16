<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Support/Polyfills/XmlWriterPolyfill.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$input = "Control\x00Zero\x01One\x02Two\x03Three\x04Four\x05Five\x06Six\x07Seven\x08Eight\x0BVerticalTab\x0CFormFeed\x0EE1\x0FE2\x10E3\x1FE4End";
$sheet->setCellValue('A1', $input);

$tempFile = sys_get_temp_dir() . '/test_inspect_zip.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempFile);

$zip = new ZipArchive();
$zip->open($tempFile);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    if (str_contains($stat['name'], '.xml')) {
        $content = $zip->getFromIndex($i);
        if (str_contains($content, 'Control')) {
            echo "FILE: " . $stat['name'] . PHP_EOL;
            echo $content . PHP_EOL;
        }
    }
}
$zip->close();
@unlink($tempFile);
