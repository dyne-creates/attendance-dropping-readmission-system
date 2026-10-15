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
use App\Services\AttendanceService;
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
        $response->assertSee('Attendance');
        $response->assertSee('CS 101');
        $response->assertSee('John Doe');
        $response->assertSee('Attendance records do not automatically drop students.');
    }

    public function test_student_cannot_access_faculty_dashboard(): void
    {
        $studentUser = $this->student->user;

        $response = $this->actingAs($studentUser)
            ->get(route('faculty.dashboard'));

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_faculty_can_record_attendance_for_session(): void
    {
        // Monday date
        $mondayDate = '2026-10-05';

        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $mondayDate,
            'grading_period' => 'first_grading',
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
        $this->assertSame('first_grading', $record->grading_period);
        $this->assertEquals('On time and engaged', $record->remarks);
        $this->assertFalse((bool) $record->is_out_of_schedule);
    }

    public function test_grading_period_is_required_when_recording_attendance(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.store'), [
                'course_id' => $this->course->course_id,
                'session_date' => '2026-10-05',
                'attendance' => [
                    [
                        'enrollment_id' => $this->enrollment->enrollment_id,
                        'status' => 'present',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('grading_period');
        $this->assertDatabaseMissing('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'session_date' => '2026-10-05',
        ]);
    }

    public function test_recording_attendance_on_unscheduled_day_marks_as_out_of_schedule(): void
    {
        // Friday date (Course is Mon/Wed)
        $fridayDate = '2026-10-09';

        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $fridayDate,
            'grading_period' => 'midterms',
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

    public function test_recording_attendance_does_not_automatically_drop_a_student_after_many_absences(): void
    {
        // Record multiple absences that previously crossed the automatic-drop limit.
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

        // Record another absence through the attendance form.
        $eighthDate = Carbon::parse('2026-08-10')->addWeeks(8)->toDateString();
        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $eighthDate,
            'grading_period' => 'first_grading',
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
        $response->assertSessionMissing('dropped_alert');

        $this->assertEquals('enrolled', $this->enrollment->fresh()->status);
        $this->assertDatabaseMissing('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
        ]);
    }

    public function test_cannot_record_future_attendance_for_already_dropped_student(): void
    {
        $this->enrollment->update(['status' => 'dropped']);

        $futureDate = '2026-11-02';
        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => $futureDate,
            'grading_period' => 'first_grading',
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
        // The faculty can make the decision before any absence threshold is reached.
        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('dropped', $this->enrollment->fresh()->status);
        $this->assertDatabaseHas('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'dropped_by' => $this->faculty->faculty_id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'student_id' => $this->student->student_id,
            'notification_type' => 'student_dropped',
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

    public function test_faculty_can_record_excused_attendance_and_it_does_not_count_towards_absences(): void
    {
        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => '2026-10-05',
            'grading_period' => 'first_grading',
            'attendance' => [
                [
                    'enrollment_id' => $this->enrollment->enrollment_id,
                    'status' => 'excused',
                    'remarks' => 'Doctor consultation with medical cert',
                ],
            ],
        ];

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record = AttendanceRecord::where('enrollment_id', $this->enrollment->enrollment_id)
            ->whereDate('session_date', '2026-10-05')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('excused', $record->status);
        $this->assertEquals('Doctor consultation with medical cert', $record->remarks);

        // Excused attendance does NOT contribute to absence hours
        $service = app(AttendanceService::class);
        $absenceHours = $service->calculateStudentAbsenceHours($this->enrollment->fresh());
        $this->assertEquals(0.0, $absenceHours);
    }

    public function test_faculty_can_record_absent_attendance_with_custom_hours_absent(): void
    {
        $payload = [
            'course_id' => $this->course->course_id,
            'session_date' => '2026-10-05',
            'grading_period' => 'first_grading',
            'attendance' => [
                [
                    'enrollment_id' => $this->enrollment->enrollment_id,
                    'status' => 'absent',
                    'hours_absent' => 2.5,
                    'remarks' => 'Left early after 30 mins',
                ],
            ],
        ];

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.store'), $payload);

        $response->assertRedirect();

        $record = AttendanceRecord::where('enrollment_id', $this->enrollment->enrollment_id)
            ->whereDate('session_date', '2026-10-05')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('absent', $record->status);
        $this->assertEquals(2.5, $record->hours_absent);

        // Service uses the custom hours_absent
        $service = app(AttendanceService::class);
        $absenceHours = $service->calculateStudentAbsenceHours($this->enrollment->fresh());
        $this->assertEquals(2.5, $absenceHours);
    }

    public function test_faculty_can_record_single_attendance_via_json_endpoint(): void
    {
        $payload = [
            'course_id' => $this->course->course_id,
            'enrollment_id' => $this->enrollment->enrollment_id,
            'session_date' => '2026-10-05',
            'status' => 'absent',
            'hours_absent' => 1.0,
            'remarks' => 'Partial absence',
            'grading_period' => 'first_grading',
        ];

        $response = $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.record'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'record' => [
                'status' => 'absent',
                'hours_absent' => 1.0,
                'remarks' => 'Partial absence',
            ],
        ]);

        $record = AttendanceRecord::where('enrollment_id', $this->enrollment->enrollment_id)
            ->whereDate('session_date', '2026-10-05')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('absent', $record->status);
        $this->assertEquals(1.0, $record->hours_absent);
    }

    public function test_blackboard_overall_attendance_score_calculation(): void
    {
        $service = app(AttendanceService::class);

        // Case 1: All present -> 100
        $records = collect([
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
        ]);
        $this->assertEquals(100.0, $service->calculateOverallScore($records));

        // Case 2: 6 present, 1 absent -> 6 / 7 = 85.71 (Matches row 3 in Blackboard Image 2!)
        $records = collect([
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'absent']),
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
        ]);
        $this->assertEquals(85.71, $service->calculateOverallScore($records));

        // Case 3: 5 present, 1 late, 1 excused -> 5.5 / 6 = 91.67 (Excused is exempt!)
        $records = collect([
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'late']),
            new AttendanceRecord(['status' => 'excused']),
            new AttendanceRecord(['status' => 'present']),
            new AttendanceRecord(['status' => 'present']),
        ]);
        $this->assertEquals(91.67, $service->calculateOverallScore($records));
    }

    public function test_dashboard_displays_blackboard_overall_grid_and_pills(): void
    {
        AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'status' => 'present',
            'grading_period' => 'first_grading',
            'is_out_of_schedule' => false,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', ['course_id' => $this->course->course_id, 'tab' => 'overall']));

        $response->assertStatus(200);
        $response->assertSee('Overall');
        $response->assertSee('100 / 100');
        $response->assertSee('Present');
        $response->assertSee('bb-grid-table', false);
    }
}
