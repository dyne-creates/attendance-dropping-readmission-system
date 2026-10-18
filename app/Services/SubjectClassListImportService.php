<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Throwable;

class SubjectClassListImportService
{
    /**
     * @return array{
     *     course_name: string,
     *     subject_code: string,
     *     section: string,
     *     school_year_label: string,
     *     semester: string,
     *     department: string,
     *     hours_per_week: int,
     *     total_semester_hours: int,
     *     schedules: array<int, array{day_of_week: string, start_time: string, end_time: string, room: string}>,
     *     students: array<int, array{student_number: string, first_name: string, last_name: string, program: string, year_level: int, email: string, row: int}>
     * }
     */
    public function parse(UploadedFile $file): array
    {
        $worksheetRows = XlsxWorkbookReader::firstWorksheetRows($file->getRealPath());
        $metadata = $worksheetRows[5][1] ?? '';

        if ($metadata === '') {
            throw new InvalidArgumentException('The workbook does not contain the expected subject class list header in cell B5.');
        }

        $course = $this->parseCourse($metadata);
        $schedule = $this->parseSchedule($metadata);
        $students = $this->parseStudents($worksheetRows);
        $hoursPerWeek = array_sum(array_map(
            fn (array $slot): int => $this->durationInHours($slot['start_time'], $slot['end_time']),
            $schedule,
        ));

        if ($hoursPerWeek < 1 || $hoursPerWeek > 40) {
            throw new InvalidArgumentException('The schedule must total between 1 and 40 whole hours per week.');
        }

        return [
            ...$course,
            'department' => $this->parseDepartment($metadata),
            'hours_per_week' => $hoursPerWeek,
            'total_semester_hours' => $hoursPerWeek * 18,
            'schedules' => $schedule,
            'students' => $students,
        ];
    }

    /**
     * @return array{course_name: string, subject_code: string, section: string, school_year_label: string, semester: string}
     */
    private function parseCourse(string $metadata): array
    {
        if (! preg_match('/Subject:\s*([A-Z0-9-]+)\s*\(\s*([^()]+?)\s*\)/i', $metadata, $subjectMatch)) {
            throw new InvalidArgumentException('The workbook header must include a subject code and course name.');
        }

        if (! preg_match('/Section\s*:\s*\d+\s*-\s*(.*?)\s*Subject:/i', $metadata, $sectionMatch)) {
            throw new InvalidArgumentException('The workbook header must include a section in the format “Section: 2 - IDB1-SD”.');
        }

        if (! preg_match('/(First|Second|Third)\s+Semester,\s*(\d{4})\s*-\s*(\d{4})/i', $metadata, $termMatch)) {
            throw new InvalidArgumentException('The workbook header must include a semester and school year, such as “First Semester, 2026 - 2027”.');
        }

        $semester = match (strtolower($termMatch[1])) {
            'first' => '1st Semester',
            'second' => '2nd Semester',
            'third' => 'Summer',
        };
        $schoolYearLabel = $termMatch[2].'-'.$termMatch[3];

        if ((int) $termMatch[3] !== (int) $termMatch[2] + 1) {
            throw new InvalidArgumentException('The school year in the workbook header must contain consecutive years.');
        }

        $courseName = trim($subjectMatch[2]);
        $subjectCode = trim($subjectMatch[1]);
        $section = trim($sectionMatch[1]);
        if (mb_strlen($courseName) > 150 || mb_strlen($subjectCode) > 20 || $section === '' || mb_strlen($section) > 20) {
            throw new InvalidArgumentException('The subject name, code, or section in the workbook header is too long or missing.');
        }

        return [
            'course_name' => $courseName,
            'subject_code' => $subjectCode,
            'section' => $section,
            'school_year_label' => $schoolYearLabel,
            'semester' => $semester,
        ];
    }

    private function parseDepartment(string $metadata): string
    {
        if (! preg_match('/SUBJECT CLASS LIST\s*(.*?)\s*(?:First|Second|Third)\s+Semester/i', $metadata, $departmentMatch)) {
            throw new InvalidArgumentException('The workbook header must include the school or department name.');
        }

        $department = trim($departmentMatch[1]);
        if ($department === '' || mb_strlen($department) > 100) {
            throw new InvalidArgumentException('The school or department name in the workbook header is invalid.');
        }

        return $department;
    }

    /**
     * @return array<int, array{day_of_week: string, start_time: string, end_time: string, room: string}>
     */
    private function parseSchedule(string $metadata): array
    {
        if (! preg_match('/Schedule:\s*(.*?)\s*Total:/i', $metadata, $scheduleMatch)) {
            throw new InvalidArgumentException('The workbook header must include the schedule before the student total.');
        }

        $dayNames = [
            'monday' => 'Mon', 'mon' => 'Mon', 'm' => 'Mon',
            'tuesday' => 'Tue', 'tue' => 'Tue', 'tu' => 'Tue', 't' => 'Tue',
            'wednesday' => 'Wed', 'wed' => 'Wed', 'w' => 'Wed',
            'thursday' => 'Thu', 'thur' => 'Thu', 'thu' => 'Thu', 'th' => 'Thu', 'r' => 'Thu',
            'friday' => 'Fri', 'fri' => 'Fri', 'f' => 'Fri',
            'saturday' => 'Sat', 'sat' => 'Sat', 'sa' => 'Sat',
            'sunday' => 'Sun', 'sun' => 'Sun', 'su' => 'Sun', 'u' => 'Sun',
        ];
        $pattern = '/(?:^|,\s*)(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Mon|Tue|Wed|Thu|Thur|Fri|Sat|Sun|Tu|Th|Sa|Su|M|T|W|R|F|S|U)\s+(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)\s*\(([^)]+)\)/i';
        preg_match_all($pattern, trim($scheduleMatch[1]), $matches, PREG_SET_ORDER);

        $schedules = [];
        foreach ($matches as $match) {
            $day = $dayNames[mb_strtolower($match[1])] ?? null;
            $startTime = $this->normalizeTime($match[2]);
            $endTime = $this->normalizeTime($match[3]);
            $room = trim($match[4]);

            if ($day === null || $endTime <= $startTime || $room === '' || mb_strlen($room) > 100) {
                throw new InvalidArgumentException('The workbook contains an invalid schedule day, time, or room.');
            }

            $schedules[] = [
                'day_of_week' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
            ];
        }

        if ($schedules === []) {
            throw new InvalidArgumentException('No schedule slots were found. Use entries such as “M 2:00 PM-5:00 PM (F111)”.');
        }

        return $schedules;
    }

    private function normalizeTime(string $time): string
    {
        try {
            return Carbon::createFromFormat('g:i A', mb_strtoupper(preg_replace('/\s+/', ' ', trim($time)) ?? $time))
                ->format('H:i');
        } catch (Throwable) {
            throw new InvalidArgumentException('The workbook contains an invalid schedule time.');
        }
    }

    private function durationInHours(string $startTime, string $endTime): int
    {
        [$startHour, $startMinute] = array_map('intval', explode(':', $startTime));
        [$endHour, $endMinute] = array_map('intval', explode(':', $endTime));
        $durationMinutes = (($endHour * 60) + $endMinute) - (($startHour * 60) + $startMinute);

        if ($durationMinutes % 60 !== 0) {
            throw new InvalidArgumentException('The imported schedule must total whole hours per meeting.');
        }

        return intdiv($durationMinutes, 60);
    }

    /**
     * @param  array<int, array<int, string>>  $worksheetRows
     * @return array<int, array{student_number: string, first_name: string, last_name: string, program: string, year_level: int, email: string, row: int}>
     */
    private function parseStudents(array $worksheetRows): array
    {
        $students = [];
        $studentNumbers = [];
        $emails = [];

        foreach ($worksheetRows as $rowNumber => $row) {
            if ($rowNumber < 7) {
                continue;
            }

            $studentNumber = trim($row[2] ?? '');
            if ($studentNumber === '') {
                continue;
            }

            $studentName = trim($row[3] ?? '');
            $program = trim($row[6] ?? '') !== '' ? trim($row[6]) : trim($row[7] ?? '');
            $year = trim($row[9] ?? '');
            $email = mb_strtolower(trim($row[10] ?? ''));

            if (! preg_match('/^[A-Za-z0-9-]{1,50}$/', $studentNumber)) {
                throw new InvalidArgumentException("The student number on row {$rowNumber} is invalid.");
            }

            if (! preg_match('/^([^,]+),\s*(.+)$/u', $studentName, $nameMatch)) {
                throw new InvalidArgumentException("The student name on row {$rowNumber} must be formatted as “LAST NAME, FIRST NAME”.");
            }

            $firstName = trim($nameMatch[2]);
            $lastName = trim($nameMatch[1]);
            if (mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100 || mb_strlen($firstName.' '.$lastName) > 150) {
                throw new InvalidArgumentException("The student name on row {$rowNumber} is too long.");
            }

            if ($program === '' || mb_strlen($program) > 100) {
                throw new InvalidArgumentException("A valid course/program is required for the student on row {$rowNumber}.");
            }

            $yearLevel = $this->yearLevel($year, $rowNumber);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 255) {
                throw new InvalidArgumentException("A valid email address is required for the student on row {$rowNumber}.");
            }

            if (isset($studentNumbers[$studentNumber])) {
                throw new InvalidArgumentException("Student number {$studentNumber} appears more than once in the workbook.");
            }

            if (isset($emails[$email])) {
                throw new InvalidArgumentException("Email address {$email} appears more than once in the workbook.");
            }

            $studentNumbers[$studentNumber] = true;
            $emails[$email] = true;
            $students[] = [
                'student_number' => $studentNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'program' => $program,
                'year_level' => $yearLevel,
                'email' => $email,
                'row' => $rowNumber,
            ];
        }

        if ($students === []) {
            throw new InvalidArgumentException('No student rows were found in the workbook.');
        }

        if (count($students) > 1000) {
            throw new InvalidArgumentException('The workbook can contain at most 1,000 students.');
        }

        return $students;
    }

    private function yearLevel(string $year, int $row): int
    {
        $yearLevel = match (mb_strtolower($year)) {
            'first year', '1st year', '1' => 1,
            'second year', '2nd year', '2' => 2,
            'third year', '3rd year', '3' => 3,
            'fourth year', '4th year', '4' => 4,
            'fifth year', '5th year', '5' => 5,
            'sixth year', '6th year', '6' => 6,
            default => null,
        };

        if ($yearLevel === null) {
            throw new InvalidArgumentException("A recognized year level is required for the student on row {$row}.");
        }

        return $yearLevel;
    }
}
