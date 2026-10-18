<?php

namespace App\Services;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use ZipArchive;

class AttendanceImportService
{
    /**
     * @return array<int, array{meeting_date: string, student_number: string, status: string, hours_absent: ?string, remarks: ?string, row: int}>
     */
    public function rows(UploadedFile $file): array
    {
        $rows = strtolower($file->getClientOriginalExtension()) === 'csv'
            ? $this->csvRows($file->getRealPath())
            : $this->excelRows($file->getRealPath());

        if (count($rows) > 1001) {
            throw new InvalidArgumentException('The file can contain at most 1,000 source rows.');
        }

        $mapped = $this->mapRows($rows);
        if (count($mapped) > 1000) {
            throw new InvalidArgumentException('The file can contain at most 1,000 attendance marks.');
        }

        return $mapped;
    }

    /** @return array<int, array<int, string>> */
    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('The uploaded CSV file could not be read.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(static fn (?string $value): string => trim((string) $value), $row);
        }
        fclose($handle);

        return $rows;
    }

    /** @return array<int, array<int, string>> */
    private function excelRows(string $path): array
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
            $workbookRelationshipsXml = $archive->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookXml === false || $workbookRelationshipsXml === false) {
                throw new InvalidArgumentException('The uploaded Excel file is missing workbook metadata.');
            }

            $workbook = $this->loadXml($workbookXml);
            $workbookXPath = new DOMXPath($workbook);
            $workbookXPath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $workbookXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $firstSheet = $workbookXPath->query('//x:sheets/x:sheet')->item(0);
            $sheetRelationshipId = $firstSheet instanceof DOMElement
                ? $firstSheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')
                : '';
            if ($sheetRelationshipId === '') {
                throw new InvalidArgumentException('The Excel workbook must contain a first worksheet.');
            }

            $relationships = $this->loadXml($workbookRelationshipsXml);
            $relationshipsXPath = new DOMXPath($relationships);
            $relationshipsXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $sheetRelationship = null;
            $sharedStringsRelationship = null;
            foreach ($relationshipsXPath->query('//r:Relationship') as $relationship) {
                if (! $relationship instanceof DOMElement) {
                    continue;
                }
                if ($relationship->getAttribute('Id') === $sheetRelationshipId) {
                    $sheetRelationship = $relationship;
                }
                if ($relationship->getAttribute('Type') === 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings') {
                    $sharedStringsRelationship = $relationship;
                }
            }
            if (! $sheetRelationship instanceof DOMElement) {
                throw new InvalidArgumentException('The Excel workbook first worksheet could not be located.');
            }

            $sheetPath = $this->resolveWorkbookPath($sheetRelationship->getAttribute('Target'));
            $sharedStrings = [];
            $sharedStringsPath = $sharedStringsRelationship instanceof DOMElement
                ? $this->resolveWorkbookPath($sharedStringsRelationship->getAttribute('Target'))
                : 'xl/sharedStrings.xml';
            $sharedXml = $archive->getFromName($sharedStringsPath);
            if ($sharedXml !== false) {
                $sharedDocument = $this->loadXml($sharedXml);
                foreach ($sharedDocument->getElementsByTagName('si') as $item) {
                    $sharedStrings[] = $item->textContent;
                }
            }

            $sheetXml = $archive->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new InvalidArgumentException('The Excel workbook first worksheet could not be read.');
            }

            $document = $this->loadXml($sheetXml);
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];
            foreach ($xpath->query('//x:sheetData/x:row') as $row) {
                if (! $row instanceof DOMElement) {
                    continue;
                }
                $cells = [];
                foreach ($xpath->query('./x:c', $row) as $cell) {
                    if (! $cell instanceof DOMElement) {
                        continue;
                    }
                    preg_match('/^[A-Z]+/', $cell->getAttribute('r'), $column);
                    $index = $this->columnIndex($column[0] ?? 'A');
                    $valueNode = $xpath->query('./x:v|./x:is', $cell)->item(0);
                    $value = $valueNode?->textContent ?? '';
                    if ($cell->getAttribute('t') === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    }
                    $cells[$index] = trim($value);
                }
                if ($cells !== []) {
                    $lastColumn = max(array_keys($cells));
                    $values = array_fill(0, $lastColumn + 1, '');
                    foreach ($cells as $column => $value) {
                        $values[$column] = $value;
                    }
                    $rows[] = $values;
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    private function resolveWorkbookPath(string $target): string
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

    private function loadXml(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new InvalidArgumentException('The Excel workbook contains invalid XML.');
        }

        return $document;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{meeting_date: string, student_number: string, status: string, hours_absent: ?string, remarks: ?string, row: int}>
     */
    private function mapRows(array $rows): array
    {
        if ($rows === []) {
            throw new InvalidArgumentException('The uploaded file is empty.');
        }

        $headerRow = array_shift($rows);
        $dateColumns = $this->dateColumns($headerRow);
        if ($dateColumns !== []) {
            return $this->mapMatrixRows($dateColumns, $rows);
        }

        array_unshift($rows, $headerRow);
        $headers = array_map(fn (string $header): string => $this->normalizeHeader($header), array_shift($rows));
        foreach (['meeting_date', 'student_number', 'status'] as $requiredHeader) {
            if (! in_array($requiredHeader, $headers, true)) {
                throw new InvalidArgumentException('The file must include meeting_date, student_number, and status columns.');
            }
        }

        $mapped = [];
        $meetingDate = null;
        foreach ($rows as $index => $row) {
            if (count(array_filter($row, static fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }
            $values = [];
            foreach ($headers as $column => $header) {
                if ($header !== '') {
                    $values[$header] = $row[$column] ?? '';
                }
            }

            $meetingDate = ($values['meeting_date'] ?? '') !== '' ? $values['meeting_date'] : $meetingDate;
            if ($meetingDate === null) {
                throw new InvalidArgumentException('A meeting date is required in the first attendance row.');
            }

            $date = $this->normalizeDate($meetingDate, $index + 2);
            $status = $this->normalizeStatus($values['status'] ?? '');
            $mapped[] = [
                'meeting_date' => $date,
                'student_number' => $values['student_number'] ?? '',
                'status' => $status,
                'hours_absent' => $values['hours_absent'] ?? null,
                'remarks' => ($values['remarks'] ?? '') !== '' ? $values['remarks'] : null,
                'row' => $index + 2,
            ];
        }

        if ($mapped === []) {
            throw new InvalidArgumentException('The file has headers but no attendance rows.');
        }

        return $mapped;
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<int, array{column: int, date: string}>
     */
    private function dateColumns(array $headerRow): array
    {
        $dateColumns = [];
        $nonEmptyHeaders = 0;

        foreach ($headerRow as $column => $header) {
            if ($column === 0 || $header === '') {
                continue;
            }

            $nonEmptyHeaders++;
            try {
                $dateColumns[] = [
                    'column' => $column,
                    'date' => $this->normalizeDate($header, 1),
                ];
            } catch (InvalidArgumentException) {
                // A row-based header is not a date matrix.
            }
        }

        if ($dateColumns !== [] && count($dateColumns) !== $nonEmptyHeaders) {
            throw new InvalidArgumentException('Every heading after the student number must be a meeting date.');
        }

        return $dateColumns;
    }

    /**
     * @param  array<int, array{column: int, date: string}>  $dateColumns
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{meeting_date: string, student_number: string, status: string, hours_absent: ?string, remarks: ?string, row: int}>
     */
    private function mapMatrixRows(array $dateColumns, array $rows): array
    {
        $mapped = [];
        foreach ($rows as $index => $row) {
            if (count(array_filter($row, static fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }

            $studentNumber = trim($row[0] ?? '');
            if (in_array(strtoupper($studentNumber), ['MALE', 'FEMALE'], true)) {
                continue;
            }
            if ($studentNumber === '') {
                throw new InvalidArgumentException('A student number is required on row '.($index + 2).'.');
            }

            foreach ($dateColumns as $dateColumn) {
                $statusValue = trim($row[$dateColumn['column']] ?? '');
                if ($statusValue === '') {
                    continue;
                }

                $status = $this->normalizeStatus($statusValue);
                if (! in_array($status, ['present', 'absent', 'late', 'excused'], true)) {
                    throw new InvalidArgumentException('Use A, P, L, or E for attendance marks on row '.($index + 2).'.');
                }

                $mapped[] = [
                    'meeting_date' => $dateColumn['date'],
                    'student_number' => $studentNumber,
                    'status' => $status,
                    'hours_absent' => null,
                    'remarks' => null,
                    'row' => $index + 2,
                ];
            }
        }

        if ($mapped === []) {
            throw new InvalidArgumentException('The file has no student attendance marks.');
        }

        return $mapped;
    }

    private function normalizeDate(string $value, int $row): string
    {
        try {
            return is_numeric($value)
                ? Carbon::create(1899, 12, 30)->addDays((int) floor((float) $value))->toDateString()
                : Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw new InvalidArgumentException('The meeting date on row '. $row . ' is invalid.');
        }
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtoupper(trim($status));

        return match ($status) {
            'A' => 'absent',
            'P' => 'present',
            'L' => 'late',
            'E' => 'excused',
            default => strtolower($status),
        };
    }
    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header));
        $header = preg_replace('/[\s-]+/', '_', $header) ?? $header;

        return match ($header) {
            'date', 'session_date', 'meeting_date' => 'meeting_date',
            'student_no', 'student_id', 'student_number', 'id_number', 'id_no' => 'student_number',
            'attendance_status', 'status' => 'status',
            'hours_absent' => 'hours_absent',
            'remarks', 'remark' => 'remarks',
            default => $header,
        };
    }
}

