<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $facultyUser;

    private FacultyMember $faculty;

    private Course $course;

    private Enrollment $enrollment;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $schoolYear = SchoolYear::create([
            'year_label' => '2025-2026',
            'semester' => '1st Semester',
            'start_date' => '2025-08-11',
            'end_date' => '2025-12-18',
            'is_archived' => false,
        ]);

        $this->facultyUser = User::factory()->faculty()->create([
            'name' => 'Prof. Alan Turing',
            'email' => 'aturing@e.ubaguio.edu',
        ]);

        $this->faculty = FacultyMember::create([
            'user_id' => $this->facultyUser->user_id,
            'full_name' => 'Prof. Alan Turing',
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);

        $this->course = Course::create([
            'faculty_id' => $this->faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => 'CS 101',
            'course_name' => 'Introduction to Computing',
            'section' => 'CS1A',
            'hours_per_week' => 3,
            'total_semester_hours' => 54.00,
        ]);

        CourseSchedule::create([
            'course_id' => $this->course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'room' => 'F-401',
        ]);

        CourseSchedule::create([
            'course_id' => $this->course->course_id,
            'day_of_week' => 'Wed',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'room' => 'F-401',
        ]);

        $studentUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'jdoe@s.ubaguio.edu',
            'role' => 'student',
        ]);

        $this->student = Student::create([
            'user_id' => $studentUser->user_id,
            'student_number' => '2025-0001',
            'last_name' => 'Doe',
            'first_name' => 'John',
            'honorifics' => 'Mr.',
            'department' => 'School of Information Technology',
            'year_level' => 1,
            'program' => 'BSCS',
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->student_id,
            'course_id' => $this->course->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);
    }

    public function test_faculty_can_view_attendance_dashboard(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Faculty Attendance Dashboard');
        $response->assertSee('CS 101');
        $response->assertSee('John Doe');
        $response->assertSee('Absence Limit: 10.8 hrs');
    }

    public function test_student_cannot_access_faculty_dashboard(): void
    {
        $studentUser = $this->student->user;

        $response = $this->actingAs($studentUser)
            ->get(route('faculty.dashboard'));

        $response->assertRedirect();
    }

    public function test_faculty_can_record_attendance_for_session(): void
    {
        // Monday date
        $mondayDate = '2026-10-05';

        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $mondayDate,
            'attendance' => [
                [
                    'enrollment_id' => $this->enrollment->enrollment_id,
                    'status' => 'present',
                    'remarks' => 'On time and engaged',
                ],
            ],
        ];

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record = AttendanceRecord::where('enrollment_id', $this->enrollment->enrollment_id)
            ->whereDate('session_date', $mondayDate)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals($this->faculty->faculty_id, $record->recorded_by);
        $this->assertEquals('present', $record->status);
        $this->assertEquals('On time and engaged', $record->remarks);
        $this->assertFalse((bool) $record->is_out_of_schedule);
    }

    public function test_recording_attendance_on_unscheduled_day_marks_as_out_of_schedule(): void
    {
        // Friday date (Course is Mon/Wed)
        $fridayDate = '2026-10-09';

        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $fridayDate,
            'attendance' => [
                [
                    'enrollment_id' => $this->enrollment->enrollment_id,
                    'status' => 'present',
                    'remarks' => 'Makeup class on Friday',
                ],
            ],
        ];

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.store'), $payload);

        $response->assertRedirect();

        $record = AttendanceRecord::where('enrollment_id', $this->enrollment->enrollment_id)
            ->whereDate('session_date', $fridayDate)
            ->first();

        $this->assertNotNull($record);
        $this->assertTrue((bool) $record->is_out_of_schedule);
    }

    public function test_student_is_automatically_dropped_when_absences_exceed_twenty_percent_threshold(): void
    {
        // 54 semester hours * 20% = 10.8 hrs threshold
        // Each session is 1.5 hrs. 8 absences = 12.0 hrs (> 10.8 hrs)
        // Record 7 absences first
        for ($i = 1; $i <= 7; $i++) {
            $date = Carbon::parse('2026-08-10')->addWeeks($i)->toDateString();
            AttendanceRecord::create([
                'enrollment_id' => $this->enrollment->enrollment_id,
                'recorded_by' => $this->faculty->faculty_id,
                'session_date' => $date,
                'status' => 'absent',
                'is_out_of_schedule' => false,
            ]);
        }

        $this->assertEquals('enrolled', $this->enrollment->fresh()->status);

        // Now record the 8th absence via attendance form
        $eighthDate = Carbon::parse('2026-08-10')->addWeeks(8)->toDateString();
        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $eighthDate,
            'attendance' => [
                [
                    'enrollment_id' => $this->enrollment->enrollment_id,
                    'status' => 'absent',
                    'remarks' => 'Eighth absence',
                ],
            ],
        ];

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('dropped_alert');

        // Verify enrollment is now dropped
        $this->assertEquals('dropped', $this->enrollment->fresh()->status);

        // Verify DroppingTransaction was created
        $this->assertDatabaseHas('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'dropped_by' => $this->faculty->faculty_id,
            'sequence_in_cycle' => 1,
        ]);
    }

    public function test_cannot_record_future_attendance_for_already_dropped_student(): void
    {
        $this->enrollment->update(['status' => 'dropped']);

        $futureDate = '2026-11-02';
        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $futureDate,
            'attendance' => [
                [
                    'enrollment_id' => $this->enrollment->enrollment_id,
                    'status' => 'present',
                ],
            ],
        ];

        $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.store'), $payload);

        // Ensure record was NOT created because student is dropped
        $this->assertDatabaseMissing('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'session_date' => $futureDate,
        ]);
    }

    public function test_faculty_can_manually_drop_student(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('dropped', $this->enrollment->fresh()->status);
        $this->assertDatabaseHas('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'dropped_by' => $this->faculty->faculty_id,
        ]);
    }

    public function test_student_attendance_history_endpoint_returns_json(): void
    {
        AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'status' => 'present',
            'remarks' => 'Active participation',
            'is_out_of_schedule' => false,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->getJson(route('faculty.enrollments.history', $this->enrollment));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'student_name',
            'student_number',
            'course',
            'records' => [
                '*' => ['attendance_id', 'session_date', 'status', 'remarks', 'is_out_of_schedule'],
            ],
        ]);
        $response->assertJsonFragment([
            'status' => 'present',
            'remarks' => 'Active participation',
        ]);
    }
}
