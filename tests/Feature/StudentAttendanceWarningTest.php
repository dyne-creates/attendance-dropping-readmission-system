<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAttendanceWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_warns_at_eighty_percent_of_maximum_absence_hours(): void
    {
        [$user, $faculty, $enrollment] = $this->createStudentEnrollment();

        foreach (['2026-10-05', '2026-10-07', '2026-10-12', '2026-10-14', '2026-10-19', '2026-10-21'] as $date) {
            AttendanceRecord::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'recorded_by' => $faculty->faculty_id,
                'session_date' => $date,
                'grading_period' => 'first_grading',
                'status' => 'absent',
            ]);
        }

        $response = $this->actingAs($user)->get(route('student.dashboard'));

        $response->assertSee('80% warning level');
        $response->assertSee('Absence warning');
        $response->assertSee('9.00 of');
        $response->assertSee('10.80 allowable absence hours');
    }

    public function test_dashboard_shows_critical_warning_when_maximum_absence_hours_are_reached(): void
    {
        [$user, $faculty, $enrollment] = $this->createStudentEnrollment();

        foreach ([
            '2026-10-05',
            '2026-10-07',
            '2026-10-12',
            '2026-10-14',
            '2026-10-19',
            '2026-10-21',
            '2026-10-26',
            '2026-10-28',
        ] as $date) {
            AttendanceRecord::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'recorded_by' => $faculty->faculty_id,
                'session_date' => $date,
                'grading_period' => 'midterms',
                'status' => 'absent',
            ]);
        }

        $response = $this->actingAs($user)->get(route('student.dashboard'));

        $response->assertSee('Maximum absence limit reached');
        $response->assertSee('12.00 of');
        $response->assertSee('10.80 allowable hours used');
    }

    public function test_lab_course_uses_its_own_weekly_hours_for_the_allowable_limit(): void
    {
        [$user, $faculty, $enrollment] = $this->createStudentEnrollment(5);

        foreach (['2026-10-05', '2026-10-07', '2026-10-12', '2026-10-14', '2026-10-19', '2026-10-21'] as $date) {
            AttendanceRecord::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'recorded_by' => $faculty->faculty_id,
                'session_date' => $date,
                'grading_period' => 'first_grading',
                'status' => 'absent',
            ]);
        }

        $response = $this->actingAs($user)->get(route('student.dashboard'));

        $response->assertSee('15.00 of');
        $response->assertSee('18.00 allowable absence hours');
        $response->assertSee('80% warning level');
    }

    public function test_dashboard_displays_only_attendance_assigned_to_a_grading_period(): void
    {
        [$user, $faculty, $enrollment] = $this->createStudentEnrollment();

        foreach ([
            ['2026-10-05', 'first_grading'],
            ['2026-10-07', 'midterms'],
            ['2026-10-09', null],
        ] as [$date, $gradingPeriod]) {
            AttendanceRecord::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'recorded_by' => $faculty->faculty_id,
                'session_date' => $date,
                'grading_period' => $gradingPeriod,
                'status' => 'absent',
            ]);
        }

        $response = $this->actingAs($user)->get(route('student.dashboard'));

        $response->assertSee('First Grading');
        $response->assertSee('Midterms');
        $response->assertSee('Monday, Oct 5, 2026');
        $response->assertSee('Wednesday, Oct 7, 2026');
        $response->assertDontSee('Friday, Oct 9, 2026');
        $response->assertDontSee('Attendance without a grading period');
    }

    /**
     * @return array{User, FacultyMember, Enrollment}
     */
    private function createStudentEnrollment(int $hoursPerWeek = 3): array
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

        $studentUser = User::factory()->create([
            'role' => 'student',
            'email' => fake()->unique()->userName().'@s.ubaguio.edu',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->user_id,
            'student_number' => fake()->unique()->numerify('2026-####'),
            'last_name' => 'Student',
            'first_name' => 'Example',
            'honorifics' => 'Mx.',
            'department' => 'School of Information Technology',
            'year_level' => 1,
            'program' => 'BSIT',
        ]);

        $course = Course::create([
            'faculty_id' => $faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => 'CS 101',
            'course_name' => 'Introduction to Computing',
            'section' => 'CS1A',
            'hours_per_week' => $hoursPerWeek,
            'total_semester_hours' => $hoursPerWeek * 18,
        ]);

        CourseSchedule::create([
            'course_id' => $course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '08:00:00',
            'end_time' => $hoursPerWeek === 5 ? '10:30:00' : '09:30:00',
        ]);
        CourseSchedule::create([
            'course_id' => $course->course_id,
            'day_of_week' => 'Wed',
            'start_time' => '08:00:00',
            'end_time' => $hoursPerWeek === 5 ? '10:30:00' : '09:30:00',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        return [$studentUser, $faculty, $enrollment];
    }
}
