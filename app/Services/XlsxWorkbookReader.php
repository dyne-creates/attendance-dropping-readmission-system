<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use ZipArchive;

class XlsxWorkbookReader
{
    /**
     * @return array<int, array<int, string>>
     */
    public static function firstWorksheetRows(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new InvalidArgumentException('Excel upload requires the PHP ZIP extension.');
        }

        if (! class_exists(DOMDocument::class) || ! class_exists(DOMXPath::class)) {
            throw new InvalidArgumentException('Excel upload requires the PHP XML and DOM extensions.');
        }

        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw new InvalidArgumentException('The uploaded Excel file is invalid or unreadable.');
        }

        try {
            $workbookXml = $archive->getFromName('xl/workbook.xml');
            $relationshipsXml = $archive->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookXml === false || $relationshipsXml === false) {
                throw new InvalidArgumentException('The uploaded Excel file is missing workbook metadata.');
            }

            $workbook = self::loadXml($workbookXml);
            $workbookXPath = new DOMXPath($workbook);
            $workbookXPath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $workbookXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $firstSheet = $workbookXPath->query('//x:sheets/x:sheet')->item(0);
            $relationshipId = $firstSheet instanceof DOMElement
                ? $firstSheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')
                : '';
            if ($relationshipId === '') {
                throw new InvalidArgumentException('The Excel workbook must contain a first worksheet.');
            }

            $relationships = self::loadXml($relationshipsXml);
            $relationshipsXPath = new DOMXPath($relationships);
            $relationshipsXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $sheetRelationship = null;
            $sharedStringsRelationship = null;

            foreach ($relationshipsXPath->query('//r:Relationship') as $relationship) {
                if (! $relationship instanceof DOMElement) {
                    continue;
                }

                if ($relationship->getAttribute('Id') === $relationshipId) {
                    $sheetRelationship = $relationship;
                }

                if ($relationship->getAttribute('Type') === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings') {
                    $sharedStringsRelationship = $relationship;
                }
            }

            if (! $sheetRelationship instanceof DOMElement) {
                throw new InvalidArgumentException('The Excel workbook first worksheet could not be located.');
            }

            $sharedStrings = [];
            $sharedStringsPath = $sharedStringsRelationship instanceof DOMElement
                ? self::resolveWorkbookPath($sharedStringsRelationship->getAttribute('Target'))
                : 'xl/sharedStrings.xml';
            $sharedStringsXml = $archive->getFromName($sharedStringsPath);
            if ($sharedStringsXml !== false) {
                $sharedStringsDocument = self::loadXml($sharedStringsXml);
                foreach ($sharedStringsDocument->getElementsByTagName('si') as $item) {
                    $sharedStrings[] = $item->textContent;
                }
            }

            $sheetPath = self::resolveWorkbookPath($sheetRelationship->getAttribute('Target'));
            $sheetXml = $archive->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new InvalidArgumentException('The Excel workbook first worksheet could not be read.');
            }

            $worksheet = self::loadXml($sheetXml);
            $worksheetXPath = new DOMXPath($worksheet);
            $worksheetXPath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];

            foreach ($worksheetXPath->query('//x:sheetData/x:row') as $row) {
                if (! $row instanceof DOMElement) {
                    continue;
                }

                $rowNumber = (int) $row->getAttribute('r');
                if ($rowNumber < 1) {
                    continue;
                }

                $cells = [];
                foreach ($worksheetXPath->query('./x:c', $row) as $cell) {
                    if (! $cell instanceof DOMElement) {
                        continue;
                    }

                    preg_match('/^[A-Z]+/', $cell->getAttribute('r'), $column);
                    $columnIndex = self::columnIndex($column[0] ?? 'A');
                    $valueNode = $worksheetXPath->query('./x:v|./x:is', $cell)->item(0);
                    $value = $valueNode?->textContent ?? '';

                    if ($cell->getAttribute('t') === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    }

                    $cells[$columnIndex] = trim($value);
                }

                if ($cells !== []) {
                    $values = array_fill(0, max(array_keys($cells)) + 1, '');
                    foreach ($cells as $column => $value) {
                        $values[$column] = $value;
                    }
                    $rows[$rowNumber] = $values;
                } else {
                    $rows[$rowNumber] = [];
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    private static function resolveWorkbookPath(string $target): string
    {
        $path = str_starts_with($target, '/') ? substr($target, 1) : 'xl/'.$target;
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private static function loadXml(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new InvalidArgumentException('The Excel workbook contains invalid XML.');
        }

        return $document;
    }

    private static function columnIndex(string $letters): int
    {
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }
}
