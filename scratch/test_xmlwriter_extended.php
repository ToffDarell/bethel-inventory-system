<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Support/Polyfills/XmlWriterPolyfill.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== EXTENDED XMLWRITER POLYFILL VERIFICATION UNDER PACKAGED PHP ===" . PHP_EOL;
echo "PHP Version: " . phpversion() . PHP_EOL;
echo "xmlwriter extension loaded: " . (extension_loaded('xmlwriter') ? 'YES' : 'NO') . PHP_EOL . PHP_EOL;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('ExtendedTest');

// Build test cells
$testCells = [
    'A1' => [
        'name' => 'Peso sign',
        'input' => '₱120,000.00 (Philippine Pesos)',
        'expected' => '₱120,000.00 (Philippine Pesos)',
    ],
    'A2' => [
        'name' => 'Apostrophe',
        'input' => "St. Luke's Hospital & Patient's Monitor",
        'expected' => "St. Luke's Hospital & Patient's Monitor",
    ],
    'A3' => [
        'name' => 'Ampersand',
        'input' => 'Bethel & Baptist & Medical & Surgical',
        'expected' => 'Bethel & Baptist & Medical & Surgical',
    ],
    'A4' => [
        'name' => 'Less-than and Greater-than',
        'input' => '<Sterilizer> & <Autoclave> <= 100°C > 50°C',
        'expected' => '<Sterilizer> & <Autoclave> <= 100°C > 50°C',
    ],
    'A5' => [
        'name' => 'Quotes',
        'input' => 'Model "Pro-Series" with \'Dual-Sensor\' and "Titanium" chassis',
        'expected' => 'Model "Pro-Series" with \'Dual-Sensor\' and "Titanium" chassis',
    ],
    'A6' => [
        'name' => 'Emoji',
        'input' => '🏥 Hospital Equipment 💊 Pharmacy ⚡ Defibrillator HeartStart XL',
        'expected' => '🏥 Hospital Equipment 💊 Pharmacy ⚡ Defibrillator HeartStart XL',
    ],
    'A7' => [
        'name' => 'Illegal XML control characters (\\x00-\\x08, \\x0B, \\x0C, \\x0E-\\x1F)',
        'input' => "Control\x00Zero\x01One\x02Two\x03Three\x04Four\x05Five\x06Six\x07Seven\x08Eight\x0BVerticalTab\x0CFormFeed\x0EE1\x0FE2\x10E3\x1FE4End",
        'expected' => "ControlZeroOneTwoThreeFourFiveSixSevenEightVerticalTabFormFeedE1E2E3E4End",
    ],
    'A8' => [
        'name' => 'Leading and trailing spaces',
        'input' => '   Bethel Baptist Hospital Preserved Spacing   ',
        'expected' => '   Bethel Baptist Hospital Preserved Spacing   ',
    ],
    'A9' => [
        'name' => 'Tabs',
        'input' => "Header1\tHeader2\tHeader3",
        'expected' => "Header1\tHeader2\tHeader3",
    ],
    'A10' => [
        'name' => 'Line breaks (CRLF and LF)',
        'input' => "Line 1: Hospital Department\r\nLine 2: Operating Room\nLine 3: Recovery Area",
        'expected' => "Line 1: Hospital Department\r\nLine 2: Operating Room\nLine 3: Recovery Area",
    ],
    'A11' => [
        'name' => '5,000-character string',
        'input' => str_repeat('Bethel-Hospital-Asset-Tracker-2026-X1-', 131) . str_repeat('Z', 5000 - (131 * 38)),
        'expected' => str_repeat('Bethel-Hospital-Asset-Tracker-2026-X1-', 131) . str_repeat('Z', 5000 - (131 * 38)),
    ],
];

// Populate spreadsheet
foreach ($testCells as $coord => $cellData) {
    $sheet->setCellValue($coord, $cellData['input']);
}

// Write to .xlsx
$tempFile = sys_get_temp_dir() . '/bethel_extended_xml_test_' . time() . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempFile);

echo "Saved .xlsx file: " . $tempFile . PHP_EOL;
echo "File size: " . filesize($tempFile) . " bytes" . PHP_EOL . PHP_EOL;

// Read back with PhpSpreadsheet
$reader = IOFactory::createReader('Xlsx');
$readSpreadsheet = $reader->load($tempFile);
$readSheet = $readSpreadsheet->getActiveSheet();

$allPassed = true;
echo "=== CELL-BY-CELL COMPARISON ===" . PHP_EOL;
foreach ($testCells as $coord => $cellData) {
    $readValue = $readSheet->getCell($coord)->getValue();
    $expected = $cellData['expected'];
    
    // Normalize newlines for comparison
    $normRead = str_replace("\r\n", "\n", (string)$readValue);
    $normExpected = str_replace("\r\n", "\n", (string)$expected);
    
    $passed = ($normRead === $normExpected);
    if (!$passed) {
        $allPassed = false;
        echo "[FAIL] {$coord} - {$cellData['name']}" . PHP_EOL;
        echo "  Expected length: " . strlen($normExpected) . " | Actual length: " . strlen($normRead) . PHP_EOL;
        echo "  Expected: " . substr($normExpected, 0, 80) . "..." . PHP_EOL;
        echo "  Actual:   " . substr($normRead, 0, 80) . "..." . PHP_EOL;
    } else {
        echo "[PASS] {$coord} - {$cellData['name']} (Length: " . strlen($normRead) . ")" . PHP_EOL;
    }
}

@unlink($tempFile);

echo PHP_EOL;
if ($allPassed) {
    echo ">>> ALL EXTENDED XMLWRITER POLYFILL TESTS PASSED SUCCESSFULLY! <<<" . PHP_EOL;
} else {
    echo ">>> SOME EXTENDED XMLWRITER POLYFILL TESTS FAILED! <<<" . PHP_EOL;
    exit(1);
}
