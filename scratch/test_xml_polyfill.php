<?php

require __DIR__ . '/../app/Support/Polyfills/XmlWriterPolyfill.php';

$w = new XMLWriter();
$w->openMemory();
$w->startDocument('1.0', 'UTF-8');
$w->startElement('root');
$w->writeAttribute('attr', 'test & "quotes" <and> ñ');
$w->writeElement('tag', 'Hello <world> & "people" ñ á é í ó ú');
$w->endElement();
$w->endDocument();

$xml = $w->outputMemory();
echo "GENERATED XML:\n" . $xml . "\n";

$doc = new DOMDocument();
$loaded = $doc->loadXML($xml);
echo "VALID_XML: " . ($loaded ? "YES" : "NO") . "\n";
echo "TAG_CONTENT: " . $doc->getElementsByTagName('tag')->item(0)->textContent . "\n";
echo "ATTR_CONTENT: " . $doc->getElementsByTagName('root')->item(0)->getAttribute('attr') . "\n";
