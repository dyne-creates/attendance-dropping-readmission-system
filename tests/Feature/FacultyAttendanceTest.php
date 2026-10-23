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
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $response->assertSee('Add Schedule Slot');
        $response->assertSee('Attendance settings');
        $response->assertSee('Attendance Settings');
        $response->assertDontSee('grading period');
        $response->assertSee('View Overall Blackboard Grid');
        $response->assertSee('Student status:')
            ->assertSee('Green: below weekly threshold')
            ->assertSee('Yellow: approaching or meeting weekly-hours drop threshold')
            ->assertSee('Red: 20% permanent-drop level or already dropped')
            ->assertSee('studentStatusOutline');
    }

    public function test_faculty_can_browse_the_weekly_subject_schedule_and_jump_to_a_date(): void
    {
        $mondaySchedule = $this->course->schedules()->where('day_of_week', 'Mon')->firstOrFail();

        $response = $this->actingAs($this->facultyUser)->get(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'date' => '2025-08-13',
        ]));

        $response->assertSee('Weekly schedule · CS 101')
            ->assertSee('Aug 11, 2025 – Aug 17, 2025')
            ->assertSee('Jump to date')
            ->assertSee('name="date" value="2025-08-13"', false)
            ->assertSee(route('faculty.dashboard', [
                'course_id' => $this->course->course_id,
                'date' => '2025-08-11',
                'tab' => 'meeting',
                'schedule_id' => $mondaySchedule->schedule_id,
            ]))
            ->assertSee(route('faculty.dashboard', [
                'course_id' => $this->course->course_id,
                'date' => '2025-08-06',
                'tab' => 'meeting',
            ]))
            ->assertSee(route('faculty.dashboard', [
                'course_id' => $this->course->course_id,
                'date' => '2025-08-20',
                'tab' => 'meeting',
            ]));
    }

    public function test_attendance_import_uses_file_dates_and_meeting_actions_are_on_date_headers(): void
    {
        $mondaySchedule = $this->course->schedules()->where('day_of_week', 'Mon')->firstOrFail();
        AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2025-08-10',
            'schedule_id' => null,
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->facultyUser)->get(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'date' => '2025-08-11',
            'tab' => 'overall',
        ]));
        $wednesdaySchedule = $this->course->schedules()->where('day_of_week', 'Wed')->firstOrFail();

        $response->assertDontSee('Selected dated meeting');
        $response->assertDontSee('attendance-import-meeting');
        $response->assertSee('meeting dates as column headings');
        $response->assertSee('@contextmenu.prevent', false);
        $response->assertSee('Sort students');
        $response->assertSee('Mark all present');
        $response->assertSee('Clear marks');
        $response->assertSee('Delete meeting');
        $this->assertNotNull($mondaySchedule);
        $this->assertNotNull($wednesdaySchedule);
    }

    public function test_faculty_can_create_a_subject_with_a_schedule_slot(): void
    {
        $this->actingAs($this->facultyUser)
            ->get(route('faculty.courses.create'))
            ->assertSee('text-gray-900 dark:text-gray-900')
            ->assertSee("day_of_week: 'Mon'", false);

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.courses.store'), [
                'course_name' => 'New Subject',
                'subject_code' => 'CS 202',
                'section' => 'CS2A',
                'hours_per_week' => 3,
                'total_semester_hours' => 54,
                'school_year_id' => $this->course->school_year_id,
                'schedules' => [
                    [
                        'day_of_week' => 'Mon',
                        'start_time' => '08:30',
                        'end_time' => '10:00',
                        'room' => 'CS-201',
                    ],
                ],
            ]);

        $response->assertRedirect(route('faculty.dashboard'));
        $createdCourse = Course::where('subject_code', 'CS 202')->firstOrFail();
        $schedule = $createdCourse->schedules()->firstOrFail();

        $this->assertSame('Mon', $schedule->day_of_week);
        $this->assertSame('08:30', Carbon::parse($schedule->start_time)->format('H:i'));
        $this->assertSame('10:00', Carbon::parse($schedule->end_time)->format('H:i'));
        $this->assertSame('CS-201', $schedule->room);
    }

    public function test_subject_creation_rejects_duplicate_schedule_slots_but_allows_another_subject_to_share_a_time(): void
    {
        $duplicateSchedule = [
            'day_of_week' => 'Mon',
            'start_time' => '08:30',
            'end_time' => '10:00',
        ];

        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.courses.create'))
            ->post(route('faculty.courses.store'), [
                'course_name' => 'Duplicate Subject',
                'subject_code' => 'CS 202',
                'section' => 'CS2A',
                'hours_per_week' => 3,
                'total_semester_hours' => 54,
                'school_year_id' => $this->course->school_year_id,
                'schedules' => [
                    $duplicateSchedule + ['room' => 'CS-201'],
                    $duplicateSchedule + ['room' => 'CS-202'],
                ],
            ]);

        $response->assertRedirect(route('faculty.courses.create'));
        $response->assertSessionHasErrors('schedules.1.start_time');
        $this->assertDatabaseMissing('courses', ['subject_code' => 'CS 202']);
        $this->assertDatabaseCount('course_schedules', 2);

        $validResponse = $this->actingAs($this->facultyUser)
            ->post(route('faculty.courses.store'), [
                'course_name' => 'Another Subject',
                'subject_code' => 'CS 203',
                'section' => 'CS2B',
                'hours_per_week' => 3,
                'total_semester_hours' => 54,
                'school_year_id' => $this->course->school_year_id,
                'schedules' => [$duplicateSchedule],
            ]);

        $validResponse->assertRedirect(route('faculty.dashboard'));
        $this->assertDatabaseHas('course_schedules', [
            'course_id' => Course::where('subject_code', 'CS 203')->firstOrFail()->course_id,
            'day_of_week' => 'Mon',
        ]);
        $sharedTimeSchedule = Course::where('subject_code', 'CS 203')->firstOrFail()->schedules()->firstOrFail();
        $this->assertSame('08:30', Carbon::parse($sharedTimeSchedule->start_time)->format('H:i'));
        $this->assertSame('10:00', Carbon::parse($sharedTimeSchedule->end_time)->format('H:i'));
    }

    public function test_subject_creation_rejects_a_room_already_reserved_by_another_subject(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.courses.create'))
            ->post(route('faculty.courses.store'), [
                'course_name' => 'Room Conflict',
                'subject_code' => 'CS 204',
                'section' => 'CS2C',
                'hours_per_week' => 3,
                'total_semester_hours' => 54,
                'school_year_id' => $this->course->school_year_id,
                'schedules' => [[
                    'day_of_week' => 'Mon',
                    'start_time' => '08:30',
                    'end_time' => '10:00',
                    'room' => ' f-401 ',
                ]],
            ]);

        $response->assertRedirect(route('faculty.courses.create'));
        $response->assertSessionHasErrors('schedules.0.room');
        $this->assertDatabaseMissing('courses', ['subject_code' => 'CS 204']);
        $this->assertDatabaseCount('course_schedules', 2);
    }

    public function test_subject_creation_allows_a_different_room_or_a_different_day_or_time(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.courses.store'), [
                'course_name' => 'Available Room Slots',
                'subject_code' => 'CS 205',
                'section' => 'CS2D',
                'hours_per_week' => 3,
                'total_semester_hours' => 54,
                'school_year_id' => $this->course->school_year_id,
                'schedules' => [
                    [
                        'day_of_week' => 'Mon',
                        'start_time' => '08:30',
                        'end_time' => '10:00',
                        'room' => 'F-402',
                    ],
                    [
                        'day_of_week' => 'Tue',
                        'start_time' => '08:30',
                        'end_time' => '10:00',
                        'room' => 'F-401',
                    ],
                    [
                        'day_of_week' => 'Mon',
                        'start_time' => '13:00',
                        'end_time' => '14:00',
                        'room' => 'F-401',
                    ],
                ],
            ]);

        $response->assertRedirect(route('faculty.dashboard'));
        $this->assertSame(3, Course::where('subject_code', 'CS 205')->firstOrFail()->schedules()->count());
    }

    public function test_subject_update_rejects_duplicate_schedule_slots(): void
    {
        $duplicateSchedule = [
            'day_of_week' => 'Tue',
            'start_time' => '08:30',
            'end_time' => '10:00',
        ];

        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.courses.edit', $this->course))
            ->put(route('faculty.courses.update', $this->course), [
                'course_name' => $this->course->course_name,
                'subject_code' => $this->course->subject_code,
                'section' => $this->course->section,
                'hours_per_week' => $this->course->hours_per_week,
                'total_semester_hours' => $this->course->total_semester_hours,
                'school_year_id' => $this->course->school_year_id,
                'schedules' => [$duplicateSchedule, $duplicateSchedule],
            ]);

        $response->assertRedirect(route('faculty.courses.edit', $this->course));
        $response->assertSessionHasErrors('schedules.1.start_time');
        $this->assertSame(2, $this->course->schedules()->count());
        $this->assertDatabaseHas('course_schedules', [
            'course_id' => $this->course->course_id,
            'day_of_week' => 'Mon',
        ]);
    }

    public function test_subject_update_rejects_a_room_reserved_by_another_subject(): void
    {
        $otherCourse = Course::create([
            'faculty_id' => $this->faculty->faculty_id,
            'school_year_id' => $this->course->school_year_id,
            'subject_code' => 'CS 206',
            'course_name' => 'Existing Room Reservation',
            'section' => 'CS2E',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        CourseSchedule::create([
            'course_id' => $otherCourse->course_id,
            'day_of_week' => 'Tue',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'room' => 'F-401',
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.courses.edit', $this->course))
            ->put(route('faculty.courses.update', $this->course), [
                'course_name' => $this->course->course_name,
                'subject_code' => $this->course->subject_code,
                'section' => $this->course->section,
                'hours_per_week' => $this->course->hours_per_week,
                'total_semester_hours' => $this->course->total_semester_hours,
                'school_year_id' => $this->course->school_year_id,
                'schedules' => [[
                    'day_of_week' => 'Tue',
                    'start_time' => '08:30',
                    'end_time' => '10:00',
                    'room' => 'F-401',
                ]],
            ]);

        $response->assertRedirect(route('faculty.courses.edit', $this->course));
        $response->assertSessionHasErrors('schedules.0.room');
        $this->assertSame(2, $this->course->schedules()->count());
    }

    public function test_faculty_can_add_a_schedule_slot_without_replacing_existing_slots(): void
    {
        $this->actingAs($this->facultyUser)
            ->get(route('faculty.courses.schedules.create', $this->course))
            ->assertSee('Add Schedule Slot')
            ->assertSee('Existing slots will be preserved.');

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.courses.schedules.store', $this->course), [
                'day_of_week' => 'Fri',
                'start_time' => '13:00',
                'end_time' => '14:30',
                'room' => 'F-402',
            ]);

        $response->assertRedirect(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'tab' => 'meeting',
        ]));
        $this->assertDatabaseHas('course_schedules', [
            'course_id' => $this->course->course_id,
            'day_of_week' => 'Fri',
            'room' => 'F-402',
        ]);
        $addedSchedule = $this->course->schedules()->where('day_of_week', 'Fri')->firstOrFail();
        $this->assertSame('13:00', Carbon::parse($addedSchedule->start_time)->format('H:i'));
        $this->assertSame('14:30', Carbon::parse($addedSchedule->end_time)->format('H:i'));
        $this->assertSame(3, $this->course->schedules()->count());
    }

    public function test_faculty_cannot_add_a_schedule_slot_to_another_facultys_subject(): void
    {
        $otherFacultyUser = User::factory()->faculty()->create();
        $otherFaculty = FacultyMember::create([
            'user_id' => $otherFacultyUser->user_id,
            'full_name' => 'Another Professor',
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);
        $otherCourse = Course::create([
            'faculty_id' => $otherFaculty->faculty_id,
            'school_year_id' => $this->course->school_year_id,
            'subject_code' => 'CS 303',
            'course_name' => 'Another Subject',
            'section' => 'CS3A',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.courses.schedules.store', $otherCourse), [
                'day_of_week' => 'Fri',
                'start_time' => '13:00',
                'end_time' => '14:30',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('course_schedules', [
            'course_id' => $otherCourse->course_id,
            'day_of_week' => 'Fri',
        ]);
    }

    public function test_schedule_slot_rejects_a_full_day_name_instead_of_the_supported_abbreviation(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.courses.schedules.create', $this->course))
            ->post(route('faculty.courses.schedules.store', $this->course), [
                'day_of_week' => 'Friday',
                'start_time' => '13:00',
                'end_time' => '14:30',
            ]);

        $response->assertRedirect(route('faculty.courses.schedules.create', $this->course));
        $response->assertSessionHasErrors('day_of_week');
        $this->assertSame(2, $this->course->schedules()->count());
    }

    public function test_schedule_slot_cannot_duplicate_an_existing_day_and_time_for_the_same_subject(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.courses.schedules.create', $this->course))
            ->post(route('faculty.courses.schedules.store', $this->course), [
                'day_of_week' => 'Mon',
                'start_time' => '08:30',
                'end_time' => '10:00',
                'room' => 'Different room does not make a different slot',
            ]);

        $response->assertRedirect(route('faculty.courses.schedules.create', $this->course));
        $response->assertSessionHasErrors('start_time');
        $this->assertSame(2, $this->course->schedules()->count());
    }

    public function test_schedule_slot_rejects_a_room_reserved_by_another_subject(): void
    {
        $otherCourse = Course::create([
            'faculty_id' => $this->faculty->faculty_id,
            'school_year_id' => $this->course->school_year_id,
            'subject_code' => 'CS 207',
            'course_name' => 'Another Subject',
            'section' => 'CS2F',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.courses.schedules.create', $otherCourse))
            ->post(route('faculty.courses.schedules.store', $otherCourse), [
                'day_of_week' => 'Mon',
                'start_time' => '08:30',
                'end_time' => '10:00',
                'room' => 'F-401',
            ]);

        $response->assertRedirect(route('faculty.courses.schedules.create', $otherCourse));
        $response->assertSessionHasErrors('room');
        $this->assertSame(0, $otherCourse->schedules()->count());
    }

    public function test_database_rejects_duplicate_schedule_slots_for_the_same_subject(): void
    {
        $this->expectException(QueryException::class);

        CourseSchedule::create([
            'course_id' => $this->course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'room' => 'Different room',
        ]);
    }

    public function test_database_rejects_an_exact_room_and_time_reservation_for_another_subject(): void
    {
        $otherCourse = Course::create([
            'faculty_id' => $this->faculty->faculty_id,
            'school_year_id' => $this->course->school_year_id,
            'subject_code' => 'CS 208',
            'course_name' => 'Another Subject',
            'section' => 'CS2G',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        $this->expectException(QueryException::class);

        CourseSchedule::create([
            'course_id' => $otherCourse->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'room' => ' f-401 ',
        ]);
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
        $this->assertNull($record->grading_period);
        $this->assertEquals('On time and engaged', $record->remarks);
        $this->assertFalse((bool) $record->is_out_of_schedule);
    }

    public function test_attendance_recording_does_not_require_a_grading_period(): void
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

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'session_date' => '2026-10-05 00:00:00',
            'status' => 'present',
            'grading_period' => null,
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

    public function test_faculty_cannot_drop_student_before_one_week_of_absences(): void
    {
        AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'status' => 'absent',
            'hours_absent' => 2.99,
            'is_out_of_schedule' => false,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', ['course_id' => $this->course->course_id]))
            ->assertViewHas('enrollmentRows', fn ($rows): bool => $rows->first()['is_drop_eligible'] === false);

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This student can be dropped to OSA after reaching 3.00 absent hours (one week of this subject); current total is 2.99 hours.');
        $this->assertSame('enrolled', $this->enrollment->fresh()->status);
        $this->assertDatabaseMissing('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'student_id' => $this->student->student_id,
            'notification_type' => 'student_dropped',
        ]);
    }

    public function test_zero_overall_score_does_not_replace_the_twenty_percent_semester_absence_threshold(): void
    {
        AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'status' => 'absent',
            'hours_absent' => 1.5,
            'is_out_of_schedule' => false,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', ['course_id' => $this->course->course_id]));

        $response->assertViewHas('enrollmentRows', fn ($rows): bool => $rows->first()['is_drop_eligible'] === false);
        $response->assertSeeText('Absent:');
        $response->assertSee('2.78', false);
        $response->assertSee('20.00%', false);
        $response->assertSee('0 / 100');
        $this->assertSame('enrolled', $this->enrollment->fresh()->status);
    }

    public function test_ajax_attendance_update_reports_drop_eligibility_at_the_twenty_percent_semester_threshold(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.record'), [
                'course_id' => $this->course->course_id,
                'enrollment_id' => $this->enrollment->enrollment_id,
                'session_date' => '2026-10-05',
                'status' => 'absent',
                'hours_absent' => 10.8,
                'grading_period' => 'first_grading',
            ]);

        $response->assertOk()
            ->assertJsonPath('stats.absence_percentage', 20)
            ->assertJsonPath('stats.maximum_absence_hours', 10.8)
            ->assertJsonPath('stats.weekly_drop_threshold_hours', 3)
            ->assertJsonPath('stats.maximum_absence_percentage', 20)
            ->assertJsonPath('stats.is_drop_eligible', true)
            ->assertJsonPath('stats.is_permanent_drop_eligible', true);
        $this->assertSame('enrolled', $this->enrollment->fresh()->status);
    }

    public function test_ajax_attendance_update_reports_weekly_drop_eligibility_before_the_permanent_threshold(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.record'), [
                'course_id' => $this->course->course_id,
                'enrollment_id' => $this->enrollment->enrollment_id,
                'session_date' => '2026-10-05',
                'status' => 'absent',
                'hours_absent' => 3,
                'grading_period' => 'first_grading',
            ]);

        $response->assertOk()
            ->assertJsonPath('stats.absent_hours', 3)
            ->assertJsonPath('stats.weekly_drop_threshold_hours', 3)
            ->assertJsonPath('stats.is_drop_eligible', true)
            ->assertJsonPath('stats.is_permanent_drop_eligible', false);
        $this->assertSame('enrolled', $this->enrollment->fresh()->status);
    }

    public function test_faculty_can_drop_student_to_osa_after_one_week_of_absences(): void
    {
        AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'status' => 'absent',
            'hours_absent' => 3,
            'is_out_of_schedule' => false,
        ]);

        $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', ['course_id' => $this->course->course_id]))
            ->assertSee('Drop student to OSA');

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('dropped', $this->enrollment->fresh()->status);
        $this->assertDatabaseHas('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'dropped_by' => $this->faculty->faculty_id,
            'drop_type' => DroppingTransaction::TYPE_WEEKLY_HOURS,
        ]);
        $this->assertDatabaseHas('notifications', [
            'student_id' => $this->student->student_id,
            'notification_type' => 'student_dropped',
        ]);
    }

    public function test_faculty_can_refer_student_to_osa_for_a_circumstance_below_the_absence_threshold(): void
    {
        $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', ['course_id' => $this->course->course_id]))
            ->assertSee('Refer student to OSA (Circumstance)');

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment), [
                'referral_type' => 'circumstance',
                'instructor_reason' => 'The student has a personal circumstance requiring OSA support.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('dropped', $this->enrollment->fresh()->status);
        $this->assertDatabaseHas('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'dropped_by' => $this->faculty->faculty_id,
            'absence_hours_at_drop' => 0,
            'drop_type' => DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL,
            'instructor_reason' => 'The student has a personal circumstance requiring OSA support.',
        ]);

        $osaUser = User::factory()->osaStaff()->create();
        $this->actingAs($osaUser)
            ->get(route('osa.archive.drops'))
            ->assertSee('Instructor referral')
            ->assertSee('The student has a personal circumstance requiring OSA support.');
    }

    public function test_faculty_cannot_submit_a_circumstance_referral_without_a_reason(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment), [
                'referral_type' => 'circumstance',
                'instructor_reason' => '',
            ]);

        $response->assertSessionHasErrors('instructor_reason');
        $this->assertSame('enrolled', $this->enrollment->fresh()->status);
        $this->assertDatabaseMissing('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
        ]);
    }

    public function test_faculty_cannot_submit_an_overlong_circumstance_referral_reason(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment), [
                'referral_type' => 'circumstance',
                'instructor_reason' => str_repeat('a', 1001),
            ]);

        $response->assertSessionHasErrors('instructor_reason');
        $this->assertSame('enrolled', $this->enrollment->fresh()->status);
        $this->assertDatabaseMissing('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
        ]);
    }

    public function test_faculty_marks_drop_as_permanent_at_twenty_percent_but_osa_can_review_readmission(): void
    {
        AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'status' => 'absent',
            'hours_absent' => 10.8,
            'is_out_of_schedule' => false,
        ]);

        $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', ['course_id' => $this->course->course_id]))
            ->assertSee('Permanent drop to OSA');

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.enrollments.drop', $this->enrollment));

        $response->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('dropped', $this->enrollment->fresh()->status);
        $this->assertDatabaseHas('dropping_transactions', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'drop_type' => DroppingTransaction::TYPE_PERMANENT,
        ]);

        $this->actingAs($this->student->user)
            ->get(route('student.readmissions.create', $this->enrollment))
            ->assertSee('OSA may still review and approve your readmission request.');
    }

    public function test_overall_and_meeting_views_share_attendance_per_schedule_slot(): void
    {
        $secondSlot = CourseSchedule::create([
            'course_id' => $this->course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '10:30:00',
            'end_time' => '12:00:00',
            'room' => 'F-402',
        ]);
        $firstSlot = $this->course->schedules()->where('start_time', '08:30:00')->firstOrFail();
        $sessionDate = '2026-10-05';

        $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.record'), [
                'course_id' => $this->course->course_id,
                'enrollment_id' => $this->enrollment->enrollment_id,
                'session_date' => $sessionDate,
                'schedule_id' => $firstSlot->schedule_id,
                'status' => 'present',
                'grading_period' => 'first_grading',
            ])
            ->assertOk();

        $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.record'), [
                'course_id' => $this->course->course_id,
                'enrollment_id' => $this->enrollment->enrollment_id,
                'session_date' => $sessionDate,
                'schedule_id' => $firstSlot->schedule_id,
                'status' => 'late',
                'grading_period' => 'first_grading',
            ])
            ->assertOk()
            ->assertJsonPath('record.status', 'late');

        $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.record'), [
                'course_id' => $this->course->course_id,
                'enrollment_id' => $this->enrollment->enrollment_id,
                'session_date' => $sessionDate,
                'schedule_id' => $secondSlot->schedule_id,
                'status' => 'absent',
                'hours_absent' => 1.5,
                'grading_period' => 'first_grading',
            ])
            ->assertOk()
            ->assertJsonPath('record.status', 'absent');

        $this->assertSame(2, AttendanceRecord::where('enrollment_id', $this->enrollment->enrollment_id)
            ->whereDate('session_date', $sessionDate)
            ->count());
        $this->assertSame('late', AttendanceRecord::where('enrollment_id', $this->enrollment->enrollment_id)
            ->whereDate('session_date', $sessionDate)
            ->where('schedule_id', $firstSlot->schedule_id)
            ->firstOrFail()
            ->status);

        $overallResponse = $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', [
                'course_id' => $this->course->course_id,
                'date' => $sessionDate,
                'tab' => 'overall',
            ]));
        $overallResponse->assertSee('bb-cell-late');
        $overallResponse->assertSee('cell-'.$this->enrollment->enrollment_id.'-'.$sessionDate.'-'.$firstSlot->schedule_id);
        $overallResponse->assertSee('cell-'.$this->enrollment->enrollment_id.'-'.$sessionDate.'-'.$secondSlot->schedule_id);

        $meetingResponse = $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', [
                'course_id' => $this->course->course_id,
                'date' => $sessionDate,
                'schedule_id' => $firstSlot->schedule_id,
                'tab' => 'meeting',
            ]));
        $meetingResponse->assertViewHas('enrollmentRows', fn ($rows): bool => $rows->first()['today_status'] === 'late');

        $secondMeetingResponse = $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', [
                'course_id' => $this->course->course_id,
                'date' => $sessionDate,
                'schedule_id' => $secondSlot->schedule_id,
                'tab' => 'meeting',
            ]));
        $secondMeetingResponse->assertViewHas('enrollmentRows', fn ($rows): bool => $rows->first()['today_status'] === 'absent');
    }

    public function test_overall_view_shows_drop_count_and_selected_meeting_actions(): void
    {
        DroppingTransaction::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'dropped_by' => $this->faculty->faculty_id,
            'absence_hours_at_drop' => 2,
            'sequence_in_cycle' => 1,
            'drop_date' => '2026-10-01',
        ]);

        $this->actingAs($this->facultyUser)
            ->get(route('faculty.dashboard', [
                'course_id' => $this->course->course_id,
                'date' => '2026-10-05',
                'tab' => 'overall',
            ]))
            ->assertViewHas('enrollmentRows', fn ($rows): bool => $rows->first()['drop_count'] === 1)
            ->assertSee('Drops')
            ->assertSee('Mark all present')
            ->assertSee('Mark all absent')
            ->assertSee('Clear marks')
            ->assertSee('Exempt meeting')
            ->assertSee('Delete meeting');
    }

    public function test_faculty_can_clear_only_the_selected_dated_meeting_marks(): void
    {
        $mondaySchedule = $this->course->schedules()->where('day_of_week', 'Mon')->firstOrFail();
        $wednesdaySchedule = $this->course->schedules()->where('day_of_week', 'Wed')->firstOrFail();
        $mondayRecord = AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'schedule_id' => $mondaySchedule->schedule_id,
            'grading_period' => 'first_grading',
            'status' => 'present',
        ]);
        $wednesdayRecord = AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-07',
            'schedule_id' => $wednesdaySchedule->schedule_id,
            'grading_period' => 'first_grading',
            'status' => 'absent',
            'hours_absent' => 1.5,
        ]);

        $this->actingAs($this->facultyUser)
            ->delete(route('faculty.attendance.meeting.clear'), [
                'course_id' => $this->course->course_id,
                'session_date' => '2026-10-05',
                'schedule_id' => $mondaySchedule->schedule_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendance_records', ['attendance_id' => $mondayRecord->attendance_id]);
        $this->assertDatabaseHas('attendance_records', ['attendance_id' => $wednesdayRecord->attendance_id]);
        $this->assertDatabaseMissing('course_schedule_cancellations', [
            'schedule_id' => $mondaySchedule->schedule_id,
            'session_date' => '2026-10-05',
        ]);
    }

    public function test_deleting_a_dated_meeting_cancels_only_that_occurrence(): void
    {
        $schedule = $this->course->schedules()->where('day_of_week', 'Mon')->firstOrFail();
        $record = AttendanceRecord::create([
            'enrollment_id' => $this->enrollment->enrollment_id,
            'recorded_by' => $this->faculty->faculty_id,
            'session_date' => '2026-10-05',
            'schedule_id' => $schedule->schedule_id,
            'grading_period' => 'first_grading',
            'status' => 'present',
        ]);

        $this->actingAs($this->facultyUser)
            ->delete(route('faculty.attendance.meeting.delete'), [
                'course_id' => $this->course->course_id,
                'session_date' => '2026-10-05',
                'schedule_id' => $schedule->schedule_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('attendance_records', ['attendance_id' => $record->attendance_id]);
        $this->assertDatabaseHas('course_schedule_cancellations', [
            'schedule_id' => $schedule->schedule_id,
            'session_date' => '2026-10-05',
            'cancelled_by' => $this->faculty->faculty_id,
        ]);
        $this->assertDatabaseCount('course_schedules', 2);

        $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.record'), [
                'course_id' => $this->course->course_id,
                'enrollment_id' => $this->enrollment->enrollment_id,
                'session_date' => '2026-10-05',
                'schedule_id' => $schedule->schedule_id,
                'status' => 'present',
                'grading_period' => 'first_grading',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('attendance_records', 0);
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

    public function test_faculty_can_import_csv_attendance_using_letter_statuses(): void
    {
        $statuses = [$this->student->student_number => 'P'];
        foreach (['2025-0002' => 'A', '2025-0003' => 'L', '2025-0004' => 'E'] as $studentNumber => $status) {
            $studentUser = User::factory()->create([
                'name' => 'Student '.substr($studentNumber, -1),
                'email' => 'student'.substr($studentNumber, -1).'@s.ubaguio.edu',
                'role' => 'student',
            ]);
            $student = Student::create([
                'user_id' => $studentUser->user_id,
                'student_number' => $studentNumber,
                'last_name' => 'Student',
                'first_name' => substr($studentNumber, -1),
                'department' => 'School of Information Technology',
                'year_level' => 1,
                'program' => 'BSCS',
            ]);
            Enrollment::create([
                'student_id' => $student->student_id,
                'course_id' => $this->course->course_id,
                'status' => 'enrolled',
                'cycle_number' => 1,
            ]);
            $statuses[$studentNumber] = $status;
        }

        $csv = "student_number,2025-08-11\n";
        foreach ($statuses as $studentNumber => $status) {
            $csv .= "{$studentNumber},{$status}\n";
        }

        $response = $this->actingAs($this->facultyUser)->post(route('faculty.attendance.import'), [
            'course_id' => $this->course->course_id,
            'file' => UploadedFile::fake()->createWithContent('attendance.csv', $csv),
        ]);

        $response->assertRedirect(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'date' => '2025-08-11',
            'tab' => 'meeting',
        ]));
        $this->assertDatabaseCount('attendance_records', 4);
        foreach ($statuses as $studentNumber => $status) {
            $enrollment = Enrollment::where('course_id', $this->course->course_id)
                ->whereHas('student', fn ($query) => $query->where('student_number', $studentNumber))
                ->firstOrFail();
            $expectedStatus = match ($status) {
                'P' => 'present',
                'A' => 'absent',
                'L' => 'late',
                'E' => 'excused',
            };
            $this->assertDatabaseHas('attendance_records', [
                'enrollment_id' => $enrollment->enrollment_id,
                'session_date' => '2025-08-11 00:00:00',
                'status' => $expectedStatus,
                'recorded_by' => $this->faculty->faculty_id,
                'schedule_id' => $this->course->schedules()->where('day_of_week', 'Mon')->value('schedule_id'),
                'grading_period' => null,
            ]);
        }
    }

    public function test_downloaded_sample_excel_imports_date_columns_and_full_word_statuses(): void
    {
        $this->student->update(['student_number' => '20260001']);

        foreach (['20260002', '20260003', '20260004'] as $index => $studentNumber) {
            $studentUser = User::factory()->create([
                'name' => 'Sample Student '.($index + 2),
                'email' => 'sample-student-'.($index + 2).'@s.ubaguio.edu',
                'role' => 'student',
            ]);
            $student = Student::create([
                'user_id' => $studentUser->user_id,
                'student_number' => $studentNumber,
                'last_name' => 'Sample',
                'first_name' => 'Student '.($index + 2),
                'department' => 'School of Information Technology',
                'year_level' => 1,
                'program' => 'BSCS',
            ]);
            Enrollment::create([
                'student_id' => $student->student_id,
                'course_id' => $this->course->course_id,
                'status' => 'enrolled',
                'cycle_number' => 1,
            ]);
        }

        $sampleWorkbook = new UploadedFile(
            public_path('attendance-import-template.xlsx'),
            'attendance-import-template.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            UPLOAD_ERR_OK,
            true,
        );

        $response = $this->actingAs($this->facultyUser)->post(route('faculty.attendance.import'), [
            'course_id' => $this->course->course_id,
            'file' => $sampleWorkbook,
        ]);

        $response->assertRedirect(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'date' => '2025-10-17',
            'tab' => 'meeting',
        ]));
        $this->assertDatabaseCount('attendance_records', 12);
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'session_date' => '2025-10-13 00:00:00',
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'session_date' => '2025-10-15 00:00:00',
            'status' => 'absent',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'session_date' => '2025-10-17 00:00:00',
            'status' => 'excused',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'session_date' => '2025-10-13 00:00:00',
            'status' => 'late',
        ]);
    }

    public function test_attendance_import_uses_the_date_in_each_row_to_choose_its_schedule(): void
    {
        $schedule = $this->course->schedules()->where('day_of_week', 'Mon')->firstOrFail();
        $csv = "meeting_date,student_number,status\n2025-08-11,{$this->student->student_number},P\n";

        $response = $this->actingAs($this->facultyUser)->post(route('faculty.attendance.import'), [
            'course_id' => $this->course->course_id,
            'file' => UploadedFile::fake()->createWithContent('attendance.csv', $csv),
        ]);

        $response->assertRedirect(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'date' => '2025-08-11',
            'tab' => 'meeting',
        ]));
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'schedule_id' => $schedule->schedule_id,
            'session_date' => '2025-08-11 00:00:00',
            'status' => 'present',
        ]);
    }

    public function test_attendance_import_maps_repeated_date_columns_to_ordered_schedule_slots(): void
    {
        $secondSchedule = CourseSchedule::create([
            'course_id' => $this->course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '10:30:00',
            'end_time' => '12:00:00',
            'room' => 'F-402',
        ]);
        $firstSchedule = $this->course->schedules()->where('day_of_week', 'Mon')->orderBy('start_time')->firstOrFail();
        $csv = "student_number,2025-08-11,2025-08-11\n{$this->student->student_number},P,A\n";

        $response = $this->actingAs($this->facultyUser)
            ->post(route('faculty.attendance.import'), [
                'course_id' => $this->course->course_id,
                'file' => UploadedFile::fake()->createWithContent('attendance.csv', $csv),
            ]);

        $response->assertRedirect(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'date' => '2025-08-11',
            'tab' => 'meeting',
        ]));
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'schedule_id' => $firstSchedule->schedule_id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'schedule_id' => $secondSchedule->schedule_id,
            'status' => 'absent',
        ]);
    }

    public function test_csv_attendance_import_rejects_students_outside_the_course_without_saving(): void
    {
        $csv = "meeting_date,student_number,status\n2025-08-11,9999-9999,P\n";

        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.dashboard', ['course_id' => $this->course->course_id]))
            ->post(route('faculty.attendance.import'), [
                'course_id' => $this->course->course_id,
                'session_date' => '2025-08-11',
                'file' => UploadedFile::fake()->createWithContent('attendance.csv', $csv),
            ]);

        $response->assertRedirect(route('faculty.dashboard', ['course_id' => $this->course->course_id]));
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_attendance_import_requires_explicit_meeting_dates_in_the_file(): void
    {
        $csv = "student_number,status\n2025-0001,P\n";

        $response = $this->actingAs($this->facultyUser)
            ->from(route('faculty.dashboard', ['course_id' => $this->course->course_id]))
            ->post(route('faculty.attendance.import'), [
                'course_id' => $this->course->course_id,
                'file' => UploadedFile::fake()->createWithContent('attendance.csv', $csv),
            ]);

        $response->assertRedirect(route('faculty.dashboard', ['course_id' => $this->course->course_id]));
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_attendance_import_reports_the_php_upload_error(): void
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'failed-attendance-upload-');
        file_put_contents($temporaryFile, 'incomplete upload');
        $failedUpload = new UploadedFile(
            $temporaryFile,
            'attendance.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            UPLOAD_ERR_INI_SIZE,
            true,
        );

        try {
            $response = $this->actingAs($this->facultyUser)
                ->from(route('faculty.dashboard', ['course_id' => $this->course->course_id]))
                ->post(route('faculty.attendance.import'), [
                    'course_id' => $this->course->course_id,
                    'session_date' => '2025-08-11',
                    'file' => $failedUpload,
                ]);

            $response->assertSessionHasErrors([
                'file' => 'The file exceeds the server upload limit. Choose a smaller file or increase upload_max_filesize and restart PHP.',
            ]);
            $this->assertDatabaseCount('attendance_records', 0);
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    public function test_faculty_can_import_date_columns_with_gender_section_rows(): void
    {
        $csv = "student_number,2025-10-13,2025-10-15\nMALE,,\n2025-0001,P,A\nFEMALE,,\n";

        $response = $this->actingAs($this->facultyUser)->post(route('faculty.attendance.import'), [
            'course_id' => $this->course->course_id,
            'file' => UploadedFile::fake()->createWithContent('attendance.csv', $csv),
        ]);

        $response->assertRedirect(route('faculty.dashboard', [
            'course_id' => $this->course->course_id,
            'date' => '2025-10-15',
            'tab' => 'meeting',
        ]));
        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'session_date' => '2025-10-13 00:00:00',
            'status' => 'present',
            'grading_period' => null,
            'schedule_id' => $this->course->schedules()->where('day_of_week', 'Mon')->value('schedule_id'),
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->enrollment_id,
            'session_date' => '2025-10-15 00:00:00',
            'status' => 'absent',
            'grading_period' => null,
            'schedule_id' => $this->course->schedules()->where('day_of_week', 'Wed')->value('schedule_id'),
        ]);
    }
}
