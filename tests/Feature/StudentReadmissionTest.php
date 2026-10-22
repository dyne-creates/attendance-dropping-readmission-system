<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\Guardian;
use App\Models\ReadmissionRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\TestCase;

class StudentReadmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dropped_student_can_view_prefilled_form_with_absences_by_period(): void
    {
        [$user, $student, $faculty, $enrollment] = $this->createDroppedStudent();
        $this->createAbsence($enrollment, $faculty, '2026-10-05', 'first_grading');
        $this->createAbsence($enrollment, $faculty, '2026-10-07', 'midterms');
        $this->createAbsence($enrollment, $faculty, '2026-10-09', null);

        $response = $this->actingAs($user)
            ->get(route('student.readmissions.create', $enrollment));

        $response->assertOk();
        $response->assertSee($student->student_number);
        $response->assertSee($student->first_name);
        $response->assertSee($student->last_name);
        $response->assertSee('Introduction to Computing');
        $response->assertSee('CS1A');
        $response->assertSee('Times dropped from this class');
        $response->assertSee('Professor Example');
        $response->assertSee('Parent Example');
        $response->assertSee('guardian@example.test');
        $response->assertSee('October 5, 2026');
        $response->assertSee('October 7, 2026');
        $response->assertDontSee('October 9, 2026');
    }

    public function test_student_dashboard_displays_readmission_action_for_dropped_class(): void
    {
        [$user, , , $enrollment, $drop] = $this->createDroppedStudent();
        $drop->update(['drop_type' => DroppingTransaction::TYPE_PERMANENT]);

        $response = $this->actingAs($user)->get(route('student.dashboard'));

        $response->assertSee('Request readmission');
        $response->assertSee('Permanent-drop case · OSA may review readmission');
        $response->assertSee(route('student.readmissions.create', $enrollment));
    }

    public function test_osa_review_includes_guardian_absences_and_policy_consent(): void
    {
        [, , $faculty, $enrollment, $drop] = $this->createDroppedStudent();
        $drop->update([
            'drop_type' => DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL,
            'instructor_reason' => 'The student needs OSA support for a personal circumstance.',
        ]);
        $this->createAbsence($enrollment, $faculty, '2026-10-05', 'first_grading');
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'dental_medical',
            'policy_acknowledged_at' => '2026-10-11 10:00:00',
            'channel' => 'online',
        ]);
        $osaUser = User::factory()->osaStaff()->create();

        $this->actingAs($osaUser)
            ->get(route('osa.readmissions'))
            ->assertSee('Instructor referral')
            ->assertSee('The student needs OSA support for a personal circumstance.');

        $response = $this->actingAs($osaUser)
            ->get(route('osa.readmissions.show', $readmission->readmission_id));

        $response->assertOk();
        $response->assertSee('Dental & Medical Concerns');
        $response->assertSee('Parent Example');
        $response->assertSee('09171234567');
        $response->assertSee('October 5, 2026');
        $response->assertSee('Policy consent');
        $response->assertSee('Acknowledged on');
        $response->assertSee('Instructor referral to OSA');
        $response->assertSee('The student needs OSA support for a personal circumstance.');
    }

    public function test_osa_can_review_readmission_for_a_permanent_drop_case(): void
    {
        [, , , , $drop] = $this->createDroppedStudent();
        $drop->update(['drop_type' => DroppingTransaction::TYPE_PERMANENT]);
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'mental_health',
            'policy_acknowledged_at' => '2026-10-11 10:00:00',
            'channel' => 'online',
        ]);
        $osaUser = User::factory()->osaStaff()->create();

        $this->actingAs($osaUser)
            ->get(route('osa.readmissions'))
            ->assertSee('Permanent')
            ->assertSee('OSA may still review readmission.');

        $this->actingAs($osaUser)
            ->get(route('osa.readmissions.show', $readmission->readmission_id))
            ->assertSee('Permanent-drop level (20% of semester hours)')
            ->assertSee('OSA may still review and approve readmission.');

        Mail::fake();

        $this->actingAs($osaUser)
            ->post(route('osa.readmissions.approve', $readmission->readmission_id))
            ->assertRedirect(route('osa.readmissions'));

        $this->assertDatabaseHas('readmission_requests', [
            'readmission_id' => $readmission->readmission_id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'enrollment_id' => $drop->enrollment_id,
            'status' => 'readmitted',
        ]);
        $this->assertDatabaseHas('notifications', [
            'readmission_id' => $readmission->readmission_id,
            'notification_type' => 'readmission_approved',
        ]);

        $this->actingAs($osaUser)
            ->get(route('osa.archive.readmissions'))
            ->assertSee('Permanent');
    }

    public function test_dropped_student_can_submit_a_readmission_request_with_a_supporting_image(): void
    {
        [$user, $student, , $enrollment] = $this->createDroppedStudent();
        Storage::fake();

        $response = $this->actingAs($user)
            ->post(route('student.readmissions.store', $enrollment), [
                'reason_code' => 'dental_medical',
                'reason_details' => 'I was unable to attend due to a medical concern.',
                'consent' => '1',
                'attachment' => UploadedFile::fake()->image('medical-certificate.png'),
                'student_id' => 999999,
                'section' => 'FORGED',
            ]);

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('success');

        $readmission = ReadmissionRequest::query()->firstOrFail();
        $this->assertSame('dental_medical', $readmission->reason_code);
        $this->assertSame('I was unable to attend due to a medical concern.', $readmission->reason_details);
        $this->assertSame('online', $readmission->channel);
        $this->assertSame('pending', $readmission->status);
        $this->assertNotNull($readmission->policy_acknowledged_at);
        $this->assertSame(
            $student->student_id,
            $readmission->droppingTransaction->enrollment->student_id,
        );

        $document = $readmission->excuseDocuments()->firstOrFail();
        $this->assertSame('supporting_document', $document->document_type);
        Storage::assertExists($document->file_path);
    }

    public function test_other_reason_requires_additional_details(): void
    {
        [$user, , , $enrollment] = $this->createDroppedStudent();

        $response = $this->actingAs($user)
            ->from(route('student.readmissions.create', $enrollment))
            ->post(route('student.readmissions.store', $enrollment), [
                'reason_code' => 'other',
                'consent' => '1',
            ]);

        $response->assertSessionHasErrors('reason_details');
        $this->assertDatabaseCount('readmission_requests', 0);
    }

    public function test_readmission_request_requires_policy_consent(): void
    {
        [$user, , , $enrollment] = $this->createDroppedStudent();

        $response = $this->actingAs($user)
            ->from(route('student.readmissions.create', $enrollment))
            ->post(route('student.readmissions.store', $enrollment), [
                'reason_code' => 'mental_health',
            ]);

        $response->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('readmission_requests', 0);
    }

    public function test_supporting_document_must_be_a_jpeg_or_png_image(): void
    {
        [$user, , , $enrollment] = $this->createDroppedStudent();
        Storage::fake();

        $response = $this->actingAs($user)
            ->from(route('student.readmissions.create', $enrollment))
            ->post(route('student.readmissions.store', $enrollment), [
                'reason_code' => 'mental_health',
                'consent' => '1',
                'attachment' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('readmission_requests', 0);
        $this->assertSame([], Storage::allFiles('readmission-documents'));
    }

    public function test_dental_medical_and_personal_family_reasons_require_an_image(): void
    {
        [$user, , , $enrollment] = $this->createDroppedStudent();

        $response = $this->actingAs($user)
            ->from(route('student.readmissions.create', $enrollment))
            ->post(route('student.readmissions.store', $enrollment), [
                'reason_code' => 'dental_medical',
                'consent' => '1',
            ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('readmission_requests', 0);
    }

    public function test_upload_size_failure_shows_a_specific_server_limit_message(): void
    {
        [$user, , , $enrollment] = $this->createDroppedStudent();
        $temporaryFile = tempnam(sys_get_temp_dir(), 'failed-upload-');
        file_put_contents($temporaryFile, 'invalid upload');
        $failedUpload = new SymfonyUploadedFile(
            $temporaryFile,
            'medical.jpg',
            'image/jpeg',
            UPLOAD_ERR_INI_SIZE,
            true,
        );

        try {
            $response = $this->actingAs($user)
                ->from(route('student.readmissions.create', $enrollment))
                ->post(route('student.readmissions.store', $enrollment), [
                    'reason_code' => 'dental_medical',
                    'consent' => '1',
                    'attachment' => $failedUpload,
                ]);

            $response->assertSessionHasErrors([
                'attachment' => 'The image exceeds the server upload limit. Choose a smaller image or increase upload_max_filesize and restart PHP.',
            ]);
            $this->assertDatabaseCount('readmission_requests', 0);
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    public function test_student_cannot_request_readmission_for_another_students_dropped_enrollment(): void
    {
        [, , , $enrollment] = $this->createDroppedStudent();
        [$otherStudentUser] = $this->createDroppedStudent('F-402');

        $response = $this->actingAs($otherStudentUser)
            ->get(route('student.readmissions.create', $enrollment));

        $response->assertNotFound();
    }

    public function test_student_cannot_request_readmission_for_an_active_enrollment(): void
    {
        [$user, , , $enrollment] = $this->createDroppedStudent();
        $enrollment->update(['status' => 'enrolled']);

        $response = $this->actingAs($user)
            ->get(route('student.readmissions.create', $enrollment));

        $response->assertNotFound();
    }

    public function test_student_cannot_submit_a_second_request_for_the_same_drop(): void
    {
        [$user, , , $enrollment, $drop] = $this->createDroppedStudent();
        ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'dental_medical',
            'channel' => 'online',
        ]);

        $response = $this->actingAs($user)
            ->post(route('student.readmissions.store', $enrollment), [
                'reason_code' => 'mental_health',
                'consent' => '1',
            ]);

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('info');
        $this->assertDatabaseCount('readmission_requests', 1);
    }

    public function test_student_can_submit_a_new_numbered_attempt_after_rejection(): void
    {
        [$user, , , $enrollment, $drop] = $this->createDroppedStudent();
        ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'attempt_number' => 1,
            'reason_code' => 'mental_health',
            'rejection_reason' => 'Please provide additional details.',
            'channel' => 'online',
            'status' => 'rejected',
        ]);

        $this->actingAs($user)
            ->get(route('student.readmissions.create', $enrollment))
            ->assertSee('Attempt 1')
            ->assertSee('Please provide additional details.')
            ->assertSee('Submitting attempt 2.');

        $response = $this->actingAs($user)
            ->post(route('student.readmissions.store', $enrollment), [
                'reason_code' => 'mental_health',
                'consent' => '1',
            ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('readmission_requests', [
            'drop_id' => $drop->drop_id,
            'attempt_number' => 2,
            'reason_code' => 'mental_health',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('readmission_requests', 2);
    }

    public function test_osa_rejection_reason_is_saved_and_shown_to_the_student(): void
    {
        [$user, , , $enrollment, $drop] = $this->createDroppedStudent();
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'mental_health',
            'channel' => 'online',
        ]);
        $osaUser = User::factory()->osaStaff()->create();
        $reason = 'Please include the missing supporting information.';

        $this->actingAs($osaUser)
            ->post(route('osa.readmissions.reject', $readmission->readmission_id), [
                'rejection_reason' => $reason,
            ])
            ->assertRedirect(route('osa.readmissions'));

        $this->assertDatabaseHas('readmission_requests', [
            'readmission_id' => $readmission->readmission_id,
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
        $this->assertDatabaseHas('notifications', [
            'readmission_id' => $readmission->readmission_id,
            'notification_type' => 'readmission_rejected',
            'message' => 'Your readmission request has been rejected. OSA feedback: '.$reason,
        ]);

        $this->actingAs($osaUser)
            ->get(route('osa.readmissions.show', $readmission->readmission_id))
            ->assertSee($reason);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertSee($reason)
            ->assertSee('Submit another attempt');
    }

    public function test_osa_cannot_reject_a_request_without_feedback(): void
    {
        [, , , , $drop] = $this->createDroppedStudent();
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'mental_health',
            'channel' => 'online',
        ]);
        $osaUser = User::factory()->osaStaff()->create();

        $this->actingAs($osaUser)
            ->from(route('osa.readmissions.show', $readmission->readmission_id))
            ->post(route('osa.readmissions.reject', $readmission->readmission_id), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertDatabaseHas('readmission_requests', [
            'readmission_id' => $readmission->readmission_id,
            'status' => 'pending',
            'rejection_reason' => null,
        ]);
    }

    public function test_osa_lists_attempt_numbers_and_keeps_processed_forms_openable(): void
    {
        [, , , , $drop] = $this->createDroppedStudent();
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'mental_health',
            'channel' => 'online',
        ]);
        $osaUser = User::factory()->osaStaff()->create();

        $this->actingAs($osaUser)
            ->get(route('osa.readmissions'))
            ->assertSee('Attempt')
            ->assertSee('1');

        $readmission->update([
            'status' => 'rejected',
            'rejection_reason' => 'Please provide more details.',
            'processed_date' => '2026-10-12',
        ]);

        $this->get(route('osa.archive.readmissions'))
            ->assertSee('View attempt 1')
            ->assertSee(route('osa.readmissions.show', $readmission->readmission_id));
    }

    public function test_osa_staff_can_download_a_private_supporting_document(): void
    {
        [, , , , $drop] = $this->createDroppedStudent();
        Storage::fake('local');
        Storage::disk('local')->put('readmission-documents/medical.png', 'private medical image');
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'dental_medical',
            'channel' => 'online',
        ]);
        $document = $readmission->excuseDocuments()->create([
            'document_type' => 'supporting_document',
            'file_path' => 'readmission-documents/medical.png',
        ]);

        $osaUser = User::factory()->osaStaff()->create();

        $response = $this->actingAs($osaUser)
            ->get(route('osa.readmissions.documents.download', $document));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=medical.png');
        $response->assertStreamedContent('private medical image');
    }

    public function test_osa_can_preview_a_private_supporting_image_for_a_processed_attempt(): void
    {
        [, , , , $drop] = $this->createDroppedStudent();
        Storage::fake('local');
        $filePath = UploadedFile::fake()->image('medical.png')->store('readmission-documents', 'local');
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'dental_medical',
            'rejection_reason' => 'Please provide more details.',
            'channel' => 'online',
            'status' => 'rejected',
        ]);
        $document = $readmission->excuseDocuments()->create([
            'document_type' => 'supporting_document',
            'file_path' => $filePath,
        ]);
        $osaUser = User::factory()->osaStaff()->create();

        $this->actingAs($osaUser)
            ->get(route('osa.readmissions.show', $readmission->readmission_id))
            ->assertSee(route('osa.readmissions.documents.preview', $document->document_id))
            ->assertSee('Please provide more details.');

        $this->actingAs($osaUser)
            ->get(route('osa.readmissions.documents.preview', $document->document_id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_student_cannot_download_an_osa_only_supporting_document(): void
    {
        [, , , , $drop] = $this->createDroppedStudent();
        Storage::fake('local');
        Storage::disk('local')->put('readmission-documents/medical.png', 'private medical image');
        $readmission = ReadmissionRequest::create([
            'drop_id' => $drop->drop_id,
            'reason_code' => 'dental_medical',
            'channel' => 'online',
        ]);
        $document = $readmission->excuseDocuments()->create([
            'document_type' => 'supporting_document',
            'file_path' => 'readmission-documents/medical.png',
        ]);
        $studentUser = User::factory()->create([
            'role' => 'student',
            'email' => fake()->unique()->userName().'@s.ubaguio.edu',
        ]);

        $response = $this->actingAs($studentUser)
            ->get(route('osa.readmissions.documents.download', $document));

        $response->assertRedirect(route('student.dashboard'));
    }

    /**
     * @return array{User, Student, FacultyMember, Enrollment, DroppingTransaction}
     */
    private function createDroppedStudent(string $room = 'F-401'): array
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
            'full_name' => 'Professor Example',
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'name' => 'Jordan Student',
            'email' => fake()->unique()->userName().'@s.ubaguio.edu',
        ]);
        $student = Student::create([
            'user_id' => $user->user_id,
            'student_number' => fake()->unique()->numerify('2026-####'),
            'last_name' => 'Student',
            'first_name' => 'Jordan',
            'honorifics' => 'Mx.',
            'department' => 'School of Information Technology',
            'year_level' => 2,
            'program' => 'BSIT',
        ]);

        Guardian::create([
            'student_id' => $student->student_id,
            'full_name' => 'Parent Example',
            'relationship' => 'Parent',
            'email' => 'guardian@example.test',
            'phone_number' => '09171234567',
        ]);

        $course = Course::create([
            'faculty_id' => $faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => 'CS 101',
            'course_name' => 'Introduction to Computing',
            'section' => 'CS1A',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);
        CourseSchedule::create([
            'course_id' => $course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'room' => $room,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'status' => 'dropped',
            'cycle_number' => 1,
        ]);
        $drop = DroppingTransaction::create([
            'enrollment_id' => $enrollment->enrollment_id,
            'dropped_by' => $faculty->faculty_id,
            'absence_hours_at_drop' => 3,
            'sequence_in_cycle' => 1,
            'drop_date' => '2026-10-01',
        ]);

        return [$user, $student, $faculty, $enrollment, $drop];
    }

    private function createAbsence(
        Enrollment $enrollment,
        FacultyMember $faculty,
        string $date,
        ?string $gradingPeriod,
    ): AttendanceRecord {
        return AttendanceRecord::create([
            'enrollment_id' => $enrollment->enrollment_id,
            'recorded_by' => $faculty->faculty_id,
            'session_date' => $date,
            'grading_period' => $gradingPeriod,
            'status' => 'absent',
        ]);
    }
}
