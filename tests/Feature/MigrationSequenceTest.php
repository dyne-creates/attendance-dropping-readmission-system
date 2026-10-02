<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\ExcuseDocument;
use App\Models\FacultyMember;
use App\Models\Guardian;
use App\Models\GuardianConsent;
use App\Models\Notification;
use App\Models\OsaStaff;
use App\Models\ReadmissionRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_15_tables_exist_in_database(): void
    {
        $tables = [
            'users',
            'school_years',
            'students',
            'faculty_members',
            'osa_staff',
            'guardians',
            'guardian_consents',
            'courses',
            'course_schedules',
            'enrollments',
            'attendance_records',
            'dropping_transactions',
            'readmission_requests',
            'excuse_documents',
            'notifications',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist.");
        }
    }

    public function test_full_attendance_dropping_and_readmission_system_workflow(): void
    {
        // 1. User creation
        $studentUser = User::create([
            'name' => 'John Doe',
            'role' => 'student',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('password'),
        ]);

        $facultyUser = User::create([
            'name' => 'Dr. Jane Smith',
            'role' => 'faculty',
            'email' => 'jane.smith@example.com',
            'password' => bcrypt('password'),
        ]);

        $osaUser = User::create([
            'name' => 'OSA Officer Admin',
            'role' => 'osa_staff',
            'email' => 'osa.admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. School Year creation
        $schoolYear = SchoolYear::create([
            'year_label' => '2025-2026',
            'semester' => '1st Semester',
            'start_date' => '2025-08-01',
            'end_date' => '2025-12-20',
            'is_archived' => false,
        ]);

        // 3. Student creation
        $student = Student::create([
            'user_id' => $studentUser->user_id,
            'student_number' => 'STU-2025-0001',
            'last_name' => 'Doe',
            'first_name' => 'John',
            'honorifics' => 'Mr.',
            'department' => 'College of Computer Studies',
            'year_level' => 3,
            'program' => 'BS Computer Science',
        ]);

        // 4. Faculty Member creation
        $faculty = FacultyMember::create([
            'user_id' => $facultyUser->user_id,
            'full_name' => 'Dr. Jane Smith',
            'department' => 'College of Computer Studies',
            'suppress_schedule_warnings' => false,
        ]);

        // 5. OSA Staff creation
        $osaStaff = OsaStaff::create([
            'user_id' => $osaUser->user_id,
        ]);

        // 6. Guardian creation
        $guardian = Guardian::create([
            'student_id' => $student->student_id,
            'full_name' => 'Robert Doe',
            'relationship' => 'Father',
            'email' => 'robert.doe@example.com',
            'phone_number' => '+639171234567',
            'is_verified' => true,
            'verification_method' => 'SMS OTP',
            'verified_at' => now(),
        ]);

        // 7. Guardian Consent creation
        $consent = GuardianConsent::create([
            'guardian_id' => $guardian->guardian_id,
            'consent_type' => 'share_dropping_details',
            'consent_form_path' => '/uploads/consents/consent_001.pdf',
            'consented_at' => now(),
            'status' => 'approved',
        ]);

        // 8. Course creation
        $course = Course::create([
            'faculty_id' => $faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => 'CS301',
            'course_name' => 'Database Systems',
            'section' => 'CS-3A',
            'hours_per_week' => 3.00,
            'total_semester_hours' => 54.00,
        ]);

        // 9. Course Schedule creation
        $schedule = CourseSchedule::create([
            'course_id' => $course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'room' => 'Lab 4',
        ]);

        // 10. Enrollment creation
        $enrollment = Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        // 11. Attendance Record creation
        $attendance = AttendanceRecord::create([
            'enrollment_id' => $enrollment->enrollment_id,
            'recorded_by' => $faculty->faculty_id,
            'session_date' => '2025-08-11',
            'status' => 'absent',
            'remarks' => 'Unexcused absence',
            'is_out_of_schedule' => false,
            'recorded_at' => now(),
        ]);

        // 12. Dropping Transaction creation
        $dropTransaction = DroppingTransaction::create([
            'enrollment_id' => $enrollment->enrollment_id,
            'dropped_by' => $faculty->faculty_id,
            'absence_hours_at_drop' => 12.00,
            'sequence_in_cycle' => 1,
            'drop_date' => '2025-09-01',
        ]);

        // 13. Readmission Request creation
        $readmission = ReadmissionRequest::create([
            'drop_id' => $dropTransaction->drop_id,
            'processed_by' => $osaStaff->osa_id,
            'reason_code' => 'MEDICAL',
            'reason_details' => 'Severe illness with hospital confinement',
            'channel' => 'online',
            'status' => 'pending',
            'notice_sent_date' => '2025-09-02',
            'processed_date' => null,
        ]);

        // 14. Excuse Document creation
        $excuseDoc = ExcuseDocument::create([
            'readmission_id' => $readmission->readmission_id,
            'document_type' => 'Medical Certificate',
            'file_path' => '/uploads/excuses/med_cert_001.pdf',
            'uploaded_at' => now(),
        ]);

        // 15. Notification creation
        $notification = Notification::create([
            'readmission_id' => $readmission->readmission_id,
            'osa_id' => $osaStaff->osa_id,
            'student_id' => $student->student_id,
            'guardian_id' => $guardian->guardian_id,
            'notification_type' => 'Readmission Notice',
            'channel' => 'email',
            'message' => 'Your readmission request has been received.',
            'sent_at' => now(),
        ]);

        // Assert relationships
        $this->assertEquals($studentUser->user_id, $student->user->user_id);
        $this->assertEquals($facultyUser->user_id, $faculty->user->user_id);
        $this->assertEquals($osaUser->user_id, $osaStaff->user->user_id);

        $this->assertEquals($student->student_id, $guardian->student->student_id);
        $this->assertEquals($guardian->guardian_id, $consent->guardian->guardian_id);

        $this->assertEquals($faculty->faculty_id, $course->facultyMember->faculty_id);
        $this->assertEquals($schoolYear->school_year_id, $course->schoolYear->school_year_id);

        $this->assertEquals($course->course_id, $schedule->course->course_id);
        $this->assertEquals($enrollment->enrollment_id, $attendance->enrollment->enrollment_id);

        $this->assertEquals($enrollment->enrollment_id, $dropTransaction->enrollment->enrollment_id);
        $this->assertEquals($dropTransaction->drop_id, $readmission->droppingTransaction->drop_id);

        $this->assertEquals($readmission->readmission_id, $excuseDoc->readmissionRequest->readmission_id);
        $this->assertEquals($readmission->readmission_id, $notification->readmissionRequest->readmission_id);
    }
}
