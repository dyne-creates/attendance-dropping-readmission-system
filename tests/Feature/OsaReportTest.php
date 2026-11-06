<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\ReadmissionRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\OsaPdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OsaReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_reports_page(): void
    {
        $response = $this->get(route('osa.reports'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_osa_reports(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($studentUser)->get(route('osa.reports'));

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_faculty_cannot_access_osa_reports(): void
    {
        $facultyUser = User::factory()->faculty()->create();

        $response = $this->actingAs($facultyUser)->get(route('osa.reports'));

        $response->assertRedirect(route('faculty.dashboard'));
    }

    public function test_osa_staff_can_view_reports_page_with_statistics_and_charts(): void
    {
        $osaUser = User::factory()->osaStaff()->create();
        $this->seedReportData();

        $response = $this->actingAs($osaUser)->get(route('osa.reports'));

        $response->assertOk();
        $response->assertSee('Reports & Analytics');
        $response->assertSee('Total Drops');
        $response->assertSee('Referrals');
        $response->assertSee('Readmission Requests');
        $response->assertSee('Excused Cases');
        $response->assertSee('Pending Cases');
        $response->assertSee('Absence Hours');
        $response->assertSee('chartDrops');
        $response->assertSee('chartExcused');
        $response->assertSee('chartTimeline');
        $response->assertSee('chartStatus');
        $response->assertSee('chartAbsence');
        $response->assertSee('Student Dropping Records');
        $response->assertSee('Preview Report');
        $response->assertDontSee('Export PDF');
        $response->assertSee('Download PDF');
        $response->assertSee('Student Attendance, Dropping & Readmission Report');
        $response->assertSee('All Recorded History');
        $response->assertSee('Apply Filters');
        $response->assertSee('Reset Filters');
        $response->assertSee('role="tablist"', false);
        $response->assertSee('data-report-tab="drops"', false);
        $response->assertSee('data-report-tab="referrals"', false);
        $response->assertSee('data-report-tab="readmissions"', false);
        $response->assertSee('data-report-tab="absences"', false);
        $response->assertSee('data-report-panel="absences"', false);
        $response->assertSee('data-live-clock', false);
        $response->assertSee('window.setInterval(updateReportClock, 1000)', false);
        $this->assertStringEndsWith('/osa/reports/preview.pdf', route('osa.reports.preview-pdf'));
    }

    public function test_filters_affect_summary_statistics_and_data_tables(): void
    {
        $osaUser = User::factory()->osaStaff()->create();
        $data = $this->seedReportData();

        // 1. Filter by program 'BSIT'
        $response = $this->actingAs($osaUser)->get(route('osa.reports', ['program' => 'BSIT']));
        $response->assertOk();
        $response->assertSee($data['student1']->student_number);
        $response->assertDontSee($data['student2']->student_number);

        // 2. Filter by date range covering only earlier drop
        $responseDate = $this->actingAs($osaUser)->get(route('osa.reports', [
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-15',
        ]));
        $responseDate->assertOk();
        $responseDate->assertSee($data['student1']->student_number);

        // 3. Tab navigation shows different tables
        $responseReferrals = $this->actingAs($osaUser)->get(route('osa.reports', ['tab' => 'referrals']));
        $responseReferrals->assertOk();
        $responseReferrals->assertSee('Faculty Referral Records');
        $responseReferrals->assertSee('Need intervention');

        $responseReadmissions = $this->actingAs($osaUser)->get(route('osa.reports', ['tab' => 'readmissions']));
        $responseReadmissions->assertOk();
        $responseReadmissions->assertSeeText('Readmission Requests & Outcomes');

        $responseAbsences = $this->actingAs($osaUser)->get(route('osa.reports', ['tab' => 'absences']));
        $responseAbsences->assertOk();
        $responseAbsences->assertSeeText('Absence & Attendance Records');
    }

    public function test_invalid_date_range_is_rejected_with_validation_error(): void
    {
        $osaUser = User::factory()->osaStaff()->create();

        $response = $this->actingAs($osaUser)->get(route('osa.reports', [
            'date_from' => '2026-10-20',
            'date_to' => '2026-10-10',
        ]));

        $response->assertSessionHasErrors('date_to');
    }

    public function test_osa_staff_can_export_report_as_pdf(): void
    {
        $osaUser = User::factory()->osaStaff()->create();
        $this->seedReportData();
        $this->travelTo(new \DateTimeImmutable('2026-11-06T19:18:50+00:00'));

        // Transparent 1x1 png base64 for testing chart image embedding
        $dummyChart = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->actingAs($osaUser)->post(route('osa.reports.export-pdf'), [
            'program' => 'BSIT',
            'chart_drops' => $dummyChart,
            'chart_excused' => $dummyChart,
            'chart_timeline' => $dummyChart,
            'chart_status' => $dummyChart,
            'chart_absence' => $dummyChart,
        ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame(
            'attachment; filename="OSA-Report-20261107-031850.pdf"',
            $response->headers->get('Content-Disposition')
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringStartsWith('%PDF-', $content);

        $previewResponse = $this->actingAs($osaUser)->post(route('osa.reports.preview-pdf'), [
            'program' => 'BSIT',
            'chart_drops' => $dummyChart,
            'chart_excused' => $dummyChart,
            'chart_timeline' => $dummyChart,
            'chart_status' => $dummyChart,
            'chart_absence' => $dummyChart,
        ]);

        $previewResponse->assertOk();
        $this->assertSame(
            'inline; filename="OSA-Report-20261107-031850.pdf"',
            $previewResponse->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith('%PDF-', (string) $previewResponse->getContent());
    }

    public function test_osa_staff_can_preview_report_pdf_inline_with_manila_generation_time(): void
    {
        $osaUser = User::factory()->osaStaff()->create();
        $reportData = $this->seedReportData();
        $this->travelTo(new \DateTimeImmutable('2026-11-06T19:18:50+00:00'));

        $pdfService = $this->mock(OsaPdfReportService::class);
        $pdfService->shouldReceive('generate')
            ->once()
            ->withArgs(static function (array $data) use ($reportData): bool {
                $drops = collect($data['drops']);

                return $data['generatedAt'] === 'November 7, 2026, 3:18 AM'
                    && $data['filters']['program'] === 'BSIT'
                    && $drops->contains(
                        fn (object $drop): bool => $drop->student_number === $reportData['student1']->student_number
                    )
                    && $drops->every(fn (object $drop): bool => $drop->program === 'BSIT');
            })
            ->andReturn('%PDF-preview');

        $response = $this->actingAs($osaUser)->post(route('osa.reports.preview-pdf'), [
            'program' => 'BSIT',
        ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('inline; filename="OSA-Report-20261107-031850.pdf"', $response->headers->get('Content-Disposition'));
        $this->assertSame('%PDF-preview', $response->getContent());
    }

    public function test_unauthorized_user_cannot_export_reports_pdf(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($studentUser)->post(route('osa.reports.export-pdf'));
        $previewResponse = $this->actingAs($studentUser)->post(route('osa.reports.preview-pdf'));

        $response->assertRedirect(route('student.dashboard'));
        $previewResponse->assertRedirect(route('student.dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    private function seedReportData(): array
    {
        $schoolYear = SchoolYear::create([
            'year_label' => '2026-2027',
            'semester' => '1st Semester',
            'start_date' => '2026-08-10',
            'end_date' => '2026-12-18',
            'is_archived' => false,
        ]);

        $facultyUser = User::factory()->faculty()->create();
        $faculty = FacultyMember::create([
            'user_id' => $facultyUser->user_id,
            'full_name' => 'Dr. Alan Turing',
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);

        $course1 = Course::create([
            'faculty_id' => $faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => 'CS 101',
            'course_name' => 'Intro to Computer Science',
            'section' => 'CS1A',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
            'is_archived' => false,
        ]);

        $course2 = Course::create([
            'faculty_id' => $faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => 'MATH 201',
            'course_name' => 'Discrete Mathematics',
            'section' => 'MATH1',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
            'is_archived' => false,
        ]);

        // Student 1: BSIT
        $u1 = User::factory()->create(['role' => 'student']);
        $student1 = Student::create([
            'user_id' => $u1->user_id,
            'student_number' => '2026-1001',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'program' => 'BSIT',
            'year_level' => 2,
            'department' => 'SIT',
        ]);

        // Student 2: BSCS
        $u2 = User::factory()->create(['role' => 'student']);
        $student2 = Student::create([
            'user_id' => $u2->user_id,
            'student_number' => '2026-2002',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'program' => 'BSCS',
            'year_level' => 3,
            'department' => 'SIT',
        ]);

        $enrollment1 = Enrollment::create([
            'student_id' => $student1->student_id,
            'course_id' => $course1->course_id,
            'status' => 'dropped',
            'cycle_number' => 1,
        ]);

        $enrollment2 = Enrollment::create([
            'student_id' => $student2->student_id,
            'course_id' => $course2->course_id,
            'status' => 'dropped',
            'cycle_number' => 1,
        ]);

        // Drop 1: Weekly hours on 2026-10-10
        $drop1 = DroppingTransaction::create([
            'enrollment_id' => $enrollment1->enrollment_id,
            'dropped_by' => $faculty->faculty_id,
            'absence_hours_at_drop' => 6.00,
            'sequence_in_cycle' => 1,
            'drop_date' => '2026-10-10',
            'drop_type' => DroppingTransaction::TYPE_WEEKLY_HOURS,
        ]);

        // Drop 2: Instructor Referral on 2026-11-01
        $drop2 = DroppingTransaction::create([
            'enrollment_id' => $enrollment2->enrollment_id,
            'dropped_by' => $faculty->faculty_id,
            'absence_hours_at_drop' => 3.00,
            'sequence_in_cycle' => 1,
            'drop_date' => '2026-11-01',
            'drop_type' => DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL,
            'instructor_reason' => 'Need intervention',
        ]);

        // Readmission for Drop 1: Excused
        $readmission1 = ReadmissionRequest::create([
            'drop_id' => $drop1->drop_id,
            'attempt_number' => 1,
            'reason_code' => 'dental_medical',
            'reason_details' => 'Medical certificate attached',
            'channel' => 'online',
            'status' => 'approved',
            'outcome' => ReadmissionRequest::OUTCOME_EXCUSED,
            'excused_from' => '2026-10-08',
            'excused_through' => '2026-10-10',
            'excused_hours_deducted' => 6.00,
            'processed_date' => '2026-10-12',
            'created_at' => '2026-10-11 09:00:00',
        ]);

        // Readmission for Drop 2: Pending
        $readmission2 = ReadmissionRequest::create([
            'drop_id' => $drop2->drop_id,
            'attempt_number' => 1,
            'reason_code' => 'mental_health',
            'channel' => 'f2f',
            'status' => 'pending',
            'created_at' => '2026-11-02 10:00:00',
        ]);

        // Attendance records
        AttendanceRecord::create([
            'enrollment_id' => $enrollment1->enrollment_id,
            'recorded_by' => $faculty->faculty_id,
            'session_date' => '2026-10-08',
            'status' => 'absent',
            'hours_absent' => 3.00,
            'remarks' => 'Fever',
        ]);

        AttendanceRecord::create([
            'enrollment_id' => $enrollment1->enrollment_id,
            'recorded_by' => $faculty->faculty_id,
            'session_date' => '2026-10-10',
            'status' => 'absent',
            'hours_absent' => 3.00,
            'remarks' => 'Doctor visit',
        ]);

        AttendanceRecord::create([
            'enrollment_id' => $enrollment2->enrollment_id,
            'recorded_by' => $faculty->faculty_id,
            'session_date' => '2026-11-01',
            'status' => 'late',
            'hours_absent' => 1.50,
            'remarks' => 'Heavy traffic',
        ]);

        return [
            'student1' => $student1,
            'student2' => $student2,
            'drop1' => $drop1,
            'drop2' => $drop2,
            'readmission1' => $readmission1,
            'readmission2' => $readmission2,
        ];
    }
}
