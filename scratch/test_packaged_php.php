<?php

echo "Packaged PHP Version: " . phpversion() . PHP_EOL;
echo "xmlwriter extension loaded: " . (extension_loaded('xmlwriter') ? 'YES' : 'NO') . PHP_EOL;

require_once __DIR__ . '/../app/Support/Polyfills/XmlWriterPolyfill.php';

$writer = new \XMLWriter();
$writer->openMemory();
$writer->startDocument('1.0', 'UTF-8', 'yes');
$writer->startElement('worksheet');
$writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$writer->startElement('sheetData');
$writer->startElement('row');
$writer->startElement('c');
$writer->writeAttribute('t', 'inlineStr');
$writer->startElement('is');

// Special characters: ampersand, quotes, brackets, and Philippine enye
$specialText = 'Bethel & Baptist Hospital - "OR-2" <Sterilizer> / Señor & Niño';
$writer->writeElement('t', $specialText);

$writer->endElement(); // is
$writer->endElement(); // c
$writer->endElement(); // row
$writer->endElement(); // sheetData
$writer->endElement(); // worksheet

$xml = $writer->outputMemory();

echo "Generated XML Length: " . strlen($xml) . " bytes" . PHP_EOL;
echo "Contains escaped ampersand (&amp;): " . (str_contains($xml, '&amp;') ? 'YES' : 'NO') . PHP_EOL;
echo "Contains escaped quote (&quot;): " . (str_contains($xml, '&quot;') ? 'YES' : 'NO') . PHP_EOL;
echo "Contains escaped less-than (&lt;): " . (str_contains($xml, '&lt;') ? 'YES' : 'NO') . PHP_EOL;
echo "Contains escaped greater-than (&gt;): " . (str_contains($xml, '&gt;') ? 'YES' : 'NO') . PHP_EOL;
echo "Contains UTF-8 enye (ñ): " . (str_contains($xml, 'ñ') ? 'YES' : 'NO') . PHP_EOL;

// Validate with DOMDocument parser
$dom = new DOMDocument();
$valid = $dom->loadXML($xml);
echo "DOMDocument loadXML valid: " . ($valid ? 'YES (Valid XML)' : 'NO (Invalid XML)') . PHP_EOL;
