<?php

declare(strict_types=1);

if (! class_exists('XMLWriter')) {
    /**
     * Pure PHP polyfill for the native XMLWriter extension.
     * Required by PhpSpreadsheet to construct OpenXML (.xlsx) files
     * in PHP environments where ext-xmlwriter is not compiled into the binary.
     */
    class XMLWriter
    {
        private string $uri = '';

        private bool $isMemory = true;

        private string $buffer = '';

        /** @var resource|null */
        private $fileHandle = null;

        private bool $indent = false;

        private string $indentString = '  ';

        /** @var array<int, string> */
        private array $elementStack = [];

        /** @var array<int, bool> */
        private array $hasChildrenStack = [];

        private bool $tagOpen = false;

        private ?string $currentAttribute = null;

        private string $attributeBuffer = '';

        public function openMemory(): bool
        {
            $this->isMemory = true;
            $this->buffer = '';
            $this->elementStack = [];
            $this->hasChildrenStack = [];
            $this->tagOpen = false;
            $this->currentAttribute = null;
            $this->attributeBuffer = '';

            return true;
        }

        public function openUri(string $uri): bool
        {
            $this->isMemory = false;
            $this->uri = $uri;
            $this->fileHandle = @fopen($uri, 'wb');
            $this->buffer = '';
            $this->elementStack = [];
            $this->hasChildrenStack = [];
            $this->tagOpen = false;
            $this->currentAttribute = null;
            $this->attributeBuffer = '';

            return $this->fileHandle !== false;
        }

        public function setIndent(bool $indent): bool
        {
            $this->indent = $indent;

            return true;
        }

        public function setIndentString(string $indentString): bool
        {
            $this->indentString = $indentString;

            return true;
        }

        /**
         * Strip XML 1.0 illegal control characters and OpenXML escaped control characters:
         * \x00-\x08, \x0B (\v), \x0C (\f), \x0E-\x1F.
         * Keeps legal whitespace: \x09 (\t), \x0A (\n), \x0D (\r).
         */
        public function stripIllegalXmlCharacters(string $string): string
        {
            // 1. Strip raw XML 1.0 illegal control characters
            $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $string) ?? '';

            // 2. Strip Excel OpenXML escaped illegal control character codes:
            // _x0000_ to _x0008_, _x000b_/_x000B_, _x000c_/_x000C_, _x000e_/_x000E_, _x000f_/_x000F_, _x0010_ to _x001f_
            // (_x0009_ tab, _x000a_ LF, _x000d_ CR are legal and preserved)
            $cleaned = preg_replace('/_x000[0-8]_|_x000[bBcC]_|_x000[eEfF]_|_x001[0-9a-fA-F]_/', '', $cleaned) ?? '';

            return $cleaned;
        }

        private function writeToTarget(string $str): void
        {
            if ($this->isMemory) {
                $this->buffer .= $str;
            } elseif ($this->fileHandle) {
                fwrite($this->fileHandle, $str);
            }
        }

        public function startDocument(?string $version = '1.0', ?string $encoding = 'UTF-8', ?string $standalone = null): bool
        {
            $str = '<?xml version="'.($version ?: '1.0').'"';
            if ($encoding) {
                $str .= ' encoding="'.$encoding.'"';
            }
            if ($standalone !== null) {
                $str .= ' standalone="'.$standalone.'"';
            }
            $str .= '?>'.($this->indent ? "\n" : '');
            $this->writeToTarget($str);

            return true;
        }

        public function endDocument(): bool
        {
            while (! empty($this->elementStack)) {
                $this->endElement();
            }

            return true;
        }

        private function closePendingOpenTag(): void
        {
            if ($this->tagOpen) {
                $this->writeToTarget('>');
                $this->tagOpen = false;
            }
        }

        public function startElement(string $name): bool
        {
            $this->closePendingOpenTag();
            if (! empty($this->hasChildrenStack)) {
                $this->hasChildrenStack[count($this->hasChildrenStack) - 1] = true;
            }
            if ($this->indent && ! empty($this->elementStack)) {
                $this->writeToTarget("\n".str_repeat($this->indentString, count($this->elementStack)));
            }
            $this->writeToTarget('<'.$name);
            $this->elementStack[] = $name;
            $this->hasChildrenStack[] = false;
            $this->tagOpen = true;

            return true;
        }

        public function startElementNs(?string $prefix, string $name, ?string $uri): bool
        {
            $fullName = $prefix ? "{$prefix}:{$name}" : $name;
            $this->startElement($fullName);
            if ($uri !== null) {
                $attrName = $prefix ? "xmlns:{$prefix}" : 'xmlns';
                $this->writeAttribute($attrName, $uri);
            }

            return true;
        }

        public function endElement(): bool
        {
            if (empty($this->elementStack)) {
                return false;
            }
            $name = array_pop($this->elementStack);
            $hasChildren = array_pop($this->hasChildrenStack);

            if ($this->tagOpen) {
                $this->writeToTarget('/>');
                $this->tagOpen = false;
            } else {
                if ($this->indent && $hasChildren) {
                    $this->writeToTarget("\n".str_repeat($this->indentString, count($this->elementStack)));
                }
                $this->writeToTarget('</'.$name.'>');
            }

            return true;
        }

        public function startAttribute(string $name): bool
        {
            $this->currentAttribute = $name;
            $this->attributeBuffer = '';

            return true;
        }

        public function startAttributeNs(?string $prefix, string $name, ?string $uri): bool
        {
            $fullName = $prefix ? "{$prefix}:{$name}" : $name;

            return $this->startAttribute($fullName);
        }

        public function endAttribute(): bool
        {
            if ($this->currentAttribute !== null) {
                $clean = $this->stripIllegalXmlCharacters($this->attributeBuffer);
                $val = htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $this->writeToTarget(' '.$this->currentAttribute.'="'.$val.'"');
                $this->currentAttribute = null;
                $this->attributeBuffer = '';
            }

            return true;
        }

        public function writeAttribute(string $name, string $value): bool
        {
            $clean = $this->stripIllegalXmlCharacters((string) $value);
            $val = htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $this->writeToTarget(' '.$name.'="'.$val.'"');

            return true;
        }

        public function writeAttributeNs(?string $prefix, string $name, ?string $uri, string $value): bool
        {
            $fullName = $prefix ? "{$prefix}:{$name}" : $name;

            return $this->writeAttribute($fullName, $value);
        }

        public function text(string $content): bool
        {
            $clean = $this->stripIllegalXmlCharacters($content);
            if ($this->currentAttribute !== null) {
                $this->attributeBuffer .= $clean;
            } else {
                $this->closePendingOpenTag();
                $escaped = htmlspecialchars($clean, ENT_XML1 | ENT_NOQUOTES, 'UTF-8');
                $this->writeToTarget($escaped);
            }

            return true;
        }

        public function writeRaw(string $content): bool
        {
            $clean = $this->stripIllegalXmlCharacters($content);
            if ($this->currentAttribute !== null) {
                $this->attributeBuffer .= $clean;
            } else {
                $this->closePendingOpenTag();
                $this->writeToTarget($clean);
            }

            return true;
        }

        public function writeElement(string $name, ?string $content = null): bool
        {
            $this->startElement($name);
            if ($content !== null) {
                $this->text($content);
            }
            $this->endElement();

            return true;
        }

        public function writeElementNs(?string $prefix, string $name, ?string $uri, ?string $content = null): bool
        {
            $this->startElementNs($prefix, $name, $uri);
            if ($content !== null) {
                $this->text($content);
            }
            $this->endElement();

            return true;
        }

        public function writeComment(string $content): bool
        {
            $clean = $this->stripIllegalXmlCharacters($content);
            $this->closePendingOpenTag();
            $this->writeToTarget('<!--'.$clean.'-->');

            return true;
        }

        public function writeCdata(string $content): bool
        {
            $clean = $this->stripIllegalXmlCharacters($content);
            $this->closePendingOpenTag();
            $this->writeToTarget('<![CDATA['.$clean.']]>');

            return true;
        }

        public function outputMemory(bool $flush = true): string
        {
            $res = $this->buffer;
            if ($flush) {
                $this->buffer = '';
            }

            return $res;
        }

        public function flush(bool $empty = true): mixed
        {
            if ($this->fileHandle) {
                fflush($this->fileHandle);
            }
            if ($this->isMemory) {
                return $this->outputMemory($empty);
            }

            return 0;
        }

        public function __destruct()
        {
            if ($this->fileHandle) {
                @fclose($this->fileHandle);
            }
        }
    }
}
