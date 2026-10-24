<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class ReadmissionExportService
{
    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public function createWorkbook(array $rows): string
    {
        $path = tempnam(storage_path('app'), 'readmissions-');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary readmission export.');
        }

        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            unlink($path);

            throw new RuntimeException('Unable to create the Excel workbook archive.');
        }

        $worksheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $worksheetRows .= '<row r="'.($rowIndex + 1).'">';

            foreach ($row as $columnIndex => $value) {
                $cellReference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $escapedValue = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $worksheetRows .= '<c r="'.$cellReference.'" t="inlineStr"><is><t xml:space="preserve">'.$escapedValue.'</t></is></c>';
            }

            $worksheetRows .= '</row>';
        }

        $lastColumn = $this->columnName(max(1, count($rows[0] ?? [])));
        $lastRow = max(1, count($rows));
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Readmissions" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'</Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<sheetData>'.$worksheetRows.'</sheetData>'
                .'<autoFilter ref="A1:'.$lastColumn.$lastRow.'"/>'
                .'</worksheet>',
        ];

        foreach ($files as $file => $contents) {
            if (! $archive->addFromString($file, $contents)) {
                $archive->close();
                unlink($path);

                throw new RuntimeException('Unable to write the Excel workbook.');
            }
        }

        if (! $archive->close()) {
            unlink($path);

            throw new RuntimeException('Unable to finish the Excel workbook.');
        }

        return $path;
    }

    private function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }
}
