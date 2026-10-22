<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class AttendanceImportService
{
    /**
     * @return array<int, array{meeting_date: string, student_number: string, status: string, hours_absent: ?string, remarks: ?string, row: int, date_occurrence: ?int}>
     */
    public function rows(UploadedFile $file, ?string $defaultMeetingDate = null): array
    {
        $rows = strtolower($file->getClientOriginalExtension()) === 'csv'
            ? $this->csvRows($file->getRealPath())
            : $this->excelRows($file->getRealPath());

        if (count($rows) > 1001) {
            throw new InvalidArgumentException('The file can contain at most 1,000 source rows.');
        }

        $mapped = $this->mapRows($rows, $defaultMeetingDate);
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
        return array_values(array_filter(
            XlsxWorkbookReader::firstWorksheetRows($path),
            static fn (array $row): bool => $row !== [],
        ));
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{meeting_date: string, student_number: string, status: string, hours_absent: ?string, remarks: ?string, row: int, date_occurrence: ?int}>
     */
    private function mapRows(array $rows, ?string $defaultMeetingDate): array
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
            if (! in_array($requiredHeader, $headers, true) && ! ($requiredHeader === 'meeting_date' && $defaultMeetingDate !== null)) {
                throw new InvalidArgumentException('The file must include student_number and status columns. Include meeting_date or choose a meeting date when uploading.');
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

            $meetingDate = ($values['meeting_date'] ?? '') !== '' ? $values['meeting_date'] : ($meetingDate ?? $defaultMeetingDate);
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
                'date_occurrence' => null,
            ];
        }

        if ($mapped === []) {
            throw new InvalidArgumentException('The file has headers but no attendance rows.');
        }

        return $mapped;
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<int, array{column: int, date: string, occurrence: int}>
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

        $occurrences = [];
        foreach ($dateColumns as &$dateColumn) {
            $dateColumn['occurrence'] = ($occurrences[$dateColumn['date']] ?? 0) + 1;
            $occurrences[$dateColumn['date']] = $dateColumn['occurrence'];
        }
        unset($dateColumn);

        return $dateColumns;
    }

    /**
     * @param  array<int, array{column: int, date: string, occurrence: int}>  $dateColumns
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{meeting_date: string, student_number: string, status: string, hours_absent: ?string, remarks: ?string, row: int, date_occurrence: ?int}>
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
                    'date_occurrence' => $dateColumn['occurrence'],
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
            throw new InvalidArgumentException('The meeting date on row '.$row.' is invalid.');
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
