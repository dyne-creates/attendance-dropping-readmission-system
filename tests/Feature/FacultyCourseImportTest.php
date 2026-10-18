<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FacultyCourseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_can_import_a_class_list_and_create_its_course_schedule_and_students(): void
    {
        $facultyUser = $this->createFaculty();
        $workbookPath = $this->createClassListWorkbook([
            ['20226648', 'BABAN, ELY HENDRICK MAGUIDE', 'BSIT-SD', 'First Year', '20226648@s.ubaguio.edu'],
            ['20255023', 'GUZMAN, RHUZL LEM-EW', 'BSIT-SD', 'First Year', '20255023@s.ubaguio.edu'],
        ]);

        try {
            $response = $this->actingAs($facultyUser)->post(route('faculty.courses.import.store'), [
                'file' => new UploadedFile(
                    $workbookPath,
                    'class-list.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    UPLOAD_ERR_OK,
                    true,
                ),
            ]);

            $course = Course::where('subject_code', 'AGLDEV1')->firstOrFail();
            $response->assertRedirect(route('faculty.dashboard', ['course_id' => $course->course_id]));

            $this->assertSame('AGILE DEVELOPMENT', $course->course_name);
            $this->assertSame('IDB1-SD', $course->section);
            $this->assertSame(5, $course->hours_per_week);
            $this->assertSame('90.00', $course->total_semester_hours);
            $this->assertSame(2, $course->schedules()->count());
            $this->assertDatabaseHas('course_schedules', [
                'course_id' => $course->course_id,
                'day_of_week' => 'Mon',
                'start_time' => '14:00:00',
                'end_time' => '17:00:00',
                'room' => 'F111',
            ]);
            $this->assertDatabaseHas('course_schedules', [
                'course_id' => $course->course_id,
                'day_of_week' => 'Fri',
                'start_time' => '14:00:00',
                'end_time' => '16:00:00',
                'room' => 'VC 01',
            ]);

            $schoolYear = SchoolYear::where('year_label', '2026-2027')->where('semester', '1st Semester')->firstOrFail();
            $this->assertSame('2026-08-10', $schoolYear->start_date->toDateString());
            $this->assertSame('2026-12-18', $schoolYear->end_date->toDateString());
            $this->assertSame(2, Student::count());
            $this->assertSame(2, Enrollment::where('course_id', $course->course_id)->count());

            $student = Student::where('student_number', '20226648')->firstOrFail();
            $this->assertSame('ELY HENDRICK MAGUIDE', $student->first_name);
            $this->assertSame('BABAN', $student->last_name);
            $this->assertSame('BSIT-SD', $student->program);
            $this->assertSame(1, $student->year_level);
            $this->assertSame('SCHOOL OF INFORMATION TECHNOLOGY', $student->department);
            $this->assertSame('student', $student->user->role);
            $this->assertTrue(Hash::check('passwords', $student->user->password));
        } finally {
            if (file_exists($workbookPath)) {
                unlink($workbookPath);
            }
        }
    }

    public function test_class_list_import_does_not_create_records_when_a_roster_email_conflicts(): void
    {
        $facultyUser = $this->createFaculty();
        $workbookPath = $this->createClassListWorkbook([
            ['20226648', 'BABAN, ELY HENDRICK MAGUIDE', 'BSIT-SD', 'First Year', $facultyUser->email],
        ]);

        try {
            $response = $this->actingAs($facultyUser)
                ->from(route('faculty.courses.import'))
                ->post(route('faculty.courses.import.store'), [
                    'file' => new UploadedFile(
                        $workbookPath,
                        'class-list.xlsx',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        UPLOAD_ERR_OK,
                        true,
                    ),
                ]);

            $response->assertRedirect(route('faculty.courses.import'));
            $response->assertSessionHasErrors('file');
            $this->assertDatabaseMissing('courses', ['subject_code' => 'AGLDEV1']);
            $this->assertDatabaseCount('students', 0);
            $this->assertDatabaseCount('school_years', 0);
        } finally {
            if (file_exists($workbookPath)) {
                unlink($workbookPath);
            }
        }
    }

    public function test_faculty_can_view_the_class_list_import_form(): void
    {
        $facultyUser = $this->createFaculty();

        $response = $this->actingAs($facultyUser)->get(route('faculty.courses.import'));

        $response->assertSee('Import Subject Class List');
        $response->assertSee('passwords');
    }

    public function test_class_list_import_reports_the_php_upload_error(): void
    {
        $facultyUser = $this->createFaculty();
        $temporaryFile = tempnam(sys_get_temp_dir(), 'failed-class-list-upload-');
        file_put_contents($temporaryFile, 'incomplete upload');
        $failedUpload = new UploadedFile(
            $temporaryFile,
            'class-list.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            UPLOAD_ERR_INI_SIZE,
            true,
        );

        try {
            $response = $this->actingAs($facultyUser)
                ->from(route('faculty.courses.import'))
                ->post(route('faculty.courses.import.store'), [
                    'file' => $failedUpload,
                ]);

            $response->assertSessionHasErrors([
                'file' => 'The file exceeds the server upload limit. Choose a smaller file or increase upload_max_filesize and restart PHP.',
            ]);
            $this->assertDatabaseCount('courses', 0);
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    private function createFaculty(): User
    {
        $facultyUser = User::factory()->faculty()->create();

        FacultyMember::create([
            'user_id' => $facultyUser->user_id,
            'full_name' => $facultyUser->name,
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);

        return $facultyUser;
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}>  $students
     */
    private function createClassListWorkbook(array $students): string
    {
        $path = tempnam(sys_get_temp_dir(), 'class-list-');
        $archive = new \ZipArchive;
        $archive->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="ClassList" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

        $rows = [
            '<row r="5">'.$this->inlineCell('B5', 'SUBJECT CLASS LISTSCHOOL OF INFORMATION TECHNOLOGYFirst Semester, 2026 - 2027Section : 2 - IDB1-SDSubject: AGLDEV1 (AGILE DEVELOPMENT)Schedule: M 2:00 PM-5:00 PM (F111), F 2:00 PM-4:00 PM (VC 01)Total:8').'</row>',
            '<row r="6">'.$this->inlineCell('B6', 'MALES').$this->inlineCell('D6', 'STUDENT NAME').$this->inlineCell('G6', 'COURSE/PROGRAM').$this->inlineCell('J6', 'LEVEL').$this->inlineCell('K6', 'EMAIL ADDRESS').'</row>',
        ];

        foreach ($students as $index => $student) {
            $rowNumber = $index + 7;
            $rows[] = '<row r="'.$rowNumber.'">'
                .$this->inlineCell('C'.$rowNumber, $student[0])
                .$this->inlineCell('D'.$rowNumber, $student[1])
                .$this->inlineCell('G'.$rowNumber, $student[2])
                .$this->inlineCell('J'.$rowNumber, $student[3])
                .$this->inlineCell('K'.$rowNumber, $student[4])
                .'</row>';
        }

        $archive->addFromString(
            'xl/worksheets/sheet1.xml',
            '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $rows).'</sheetData></worksheet>',
        );
        $archive->close();

        return $path;
    }

    private function inlineCell(string $cell, string $value): string
    {
        return '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
    }
}
