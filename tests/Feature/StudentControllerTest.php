<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_current_classes_and_attendance_totals_for_authenticated_student(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $studentUser->user_id,
            'student_number' => 'STU-1001',
            'last_name' => 'Student',
            'first_name' => 'Current',
            'honorifics' => 'Mx.',
            'department' => 'Computer Studies',
            'year_level' => 2,
            'program' => 'BS Computer Science',
        ]);

        $faculty = FacultyMember::create([
            'user_id' => User::factory()->create(['role' => 'faculty'])->user_id,
            'full_name' => 'Alex Faculty',
            'department' => 'Computer Studies',
        ]);

        $activeSchoolYear = SchoolYear::create([
            'year_label' => '2026-2027',
            'semester' => '1st Semester',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-20',
            'is_archived' => false,
        ]);

        $currentCourse = $this->createCourse(
            $faculty,
            $activeSchoolYear,
            'CS101',
            'Introduction to Computing',
            'IDB - SD',
        );

        $currentEnrollment = Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $currentCourse->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        $attendanceDays = [
            '2026-09-01' => 'present',
            '2026-09-03' => 'absent',
            '2026-09-08' => 'present',
            '2026-09-10' => 'late',
            '2026-09-15' => 'present',
            '2026-09-17' => 'late',
            '2026-09-22' => 'present',
        ];

        foreach ($attendanceDays as $sessionDate => $status) {
            AttendanceRecord::create([
                'enrollment_id' => $currentEnrollment->enrollment_id,
                'recorded_by' => $faculty->faculty_id,
                'session_date' => $sessionDate,
                'grading_period' => 'midterms',
                'status' => $status,
                'recorded_at' => "{$sessionDate} 10:00:00",
            ]);
        }

        $readmittedCourse = $this->createCourse($faculty, $activeSchoolYear, 'CS102', 'Data Structures');
        Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $readmittedCourse->course_id,
            'status' => 'readmitted',
            'cycle_number' => 2,
        ]);

        $droppedCourse = $this->createCourse($faculty, $activeSchoolYear, 'CS103', 'Dropped Course');
        Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $droppedCourse->course_id,
            'status' => 'dropped',
            'cycle_number' => 1,
        ]);

        $archivedSchoolYear = SchoolYear::create([
            'year_label' => '2025-2026',
            'semester' => '2nd Semester',
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-30',
            'is_archived' => true,
        ]);
        $archivedCourse = $this->createCourse($faculty, $archivedSchoolYear, 'CS104', 'Archived Course');
        Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $archivedCourse->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        $otherStudentUser = User::factory()->create(['role' => 'student']);
        $otherStudent = Student::create([
            'user_id' => $otherStudentUser->user_id,
            'student_number' => 'STU-1002',
            'last_name' => 'Other',
            'first_name' => 'Student',
            'honorifics' => 'Mx.',
            'department' => 'Computer Studies',
            'year_level' => 2,
            'program' => 'BS Computer Science',
        ]);
        $otherStudentCourse = $this->createCourse($faculty, $activeSchoolYear, 'CS201', 'Another Student Course');
        Enrollment::create([
            'student_id' => $otherStudent->student_id,
            'course_id' => $otherStudentCourse->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        $this->actingAs($studentUser)
            ->get(route('dashboard.student'))
            ->assertOk()
            ->assertSee('Introduction to Computing')
            ->assertSee('Data Structures')
            ->assertSee('CS101')
            ->assertSee('IDB - SD')
            ->assertSeeInOrder(['Total days', '7', 'Present', '4', 'Absent', '1', 'Late', '2'])
            ->assertSeeInOrder(['First Grading', 'Days', '0', 'Present', '0', 'Absent', '0', 'Late', '0'])
            ->assertSeeInOrder(['Midterms', 'Days', '7', 'Present', '4', 'Absent', '1', 'Late', '2'])
            ->assertSeeInOrder(['Finals', 'Days', '0', 'Present', '0', 'Absent', '0', 'Late', '0'])
            ->assertSeeInOrder([
                'Tuesday, Sep 1, 2026', 'Present',
                'Thursday, Sep 3, 2026', 'Absent',
                'Tuesday, Sep 8, 2026', 'Present',
                'Thursday, Sep 10, 2026', 'Late',
                'Tuesday, Sep 15, 2026', 'Present',
                'Thursday, Sep 17, 2026', 'Late',
                'Tuesday, Sep 22, 2026', 'Present',
            ])
            ->assertSee('2026-2027')
            ->assertSee('Your subjects by semester')
            ->assertDontSee('Overall Attendance')
            ->assertSee('Dropped Course')
            ->assertSee('Dropped')
            ->assertSee('Archived Course')
            ->assertDontSee('Another Student Course');

        AttendanceRecord::create([
            'enrollment_id' => $currentEnrollment->enrollment_id,
            'recorded_by' => $faculty->faculty_id,
            'session_date' => '2026-09-24',
            'status' => 'absent',
            'recorded_at' => '2026-09-24 10:00:00',
        ]);

        $this->actingAs($studentUser)
            ->get(route('dashboard.student'))
            ->assertOk()
            ->assertSee('Attendance without a grading period (1 days)')
            ->assertSee('Thursday, Sep 24, 2026');
    }

    public function test_dashboard_redirects_unauthenticated_visitors_to_login(): void
    {
        $this->get(route('dashboard.student'))
            ->assertRedirect(route('login.select'));
    }

    public function test_dashboard_shows_an_empty_state_when_student_has_no_classes(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser)
            ->get(route('dashboard.student'))
            ->assertOk()
            ->assertSee('You don’t have any subjects yet.')
            ->assertDontSee('Total days');
    }

    private function createCourse(
        FacultyMember $faculty,
        SchoolYear $schoolYear,
        string $subjectCode,
        string $courseName,
        string $section = 'A',
    ): Course {
        return Course::create([
            'faculty_id' => $faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => $subjectCode,
            'course_name' => $courseName,
            'section' => $section,
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);
    }
}
