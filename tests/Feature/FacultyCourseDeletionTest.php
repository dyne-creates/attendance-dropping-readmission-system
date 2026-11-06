<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyCourseDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $facultyUser;

    private FacultyMember $faculty;

    private SchoolYear $schoolYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolYear = SchoolYear::create([
            'year_label' => '2026-2027',
            'semester' => '1st Semester',
            'start_date' => '2026-08-10',
            'end_date' => '2026-12-18',
            'is_archived' => false,
        ]);

        $this->facultyUser = User::factory()->faculty()->create([
            'name' => 'Prof. Katherine Johnson',
            'email' => 'kjohnson@e.ubaguio.edu',
        ]);

        $this->faculty = FacultyMember::create([
            'user_id' => $this->facultyUser->user_id,
            'full_name' => 'Prof. Katherine Johnson',
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);
    }

    public function test_faculty_can_delete_course_with_schedules_when_no_students_are_enrolled(): void
    {
        $course = Course::create([
            'faculty_id' => $this->faculty->faculty_id,
            'school_year_id' => $this->schoolYear->school_year_id,
            'course_name' => 'Advanced Algorithms',
            'subject_code' => 'CS401',
            'section' => 'A1',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        $schedule1 = CourseSchedule::create([
            'course_id' => $course->course_id,
            'day_of_week' => 'Mon',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'room' => 'CS-101',
        ]);

        $schedule2 = CourseSchedule::create([
            'course_id' => $course->course_id,
            'day_of_week' => 'Wed',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'room' => 'CS-101',
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->delete(route('faculty.courses.destroy', $course));

        $response->assertRedirect(route('faculty.subjects'));
        $response->assertSessionHas('success', "Subject 'CS401' was deleted successfully.");

        $this->assertModelMissing($course);
        $this->assertModelMissing($schedule1);
        $this->assertModelMissing($schedule2);
    }

    public function test_faculty_cannot_delete_course_when_students_are_enrolled(): void
    {
        $course = Course::create([
            'faculty_id' => $this->faculty->faculty_id,
            'school_year_id' => $this->schoolYear->school_year_id,
            'course_name' => 'Database Systems',
            'subject_code' => 'CS202',
            'section' => 'B2',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        $schedule = CourseSchedule::create([
            'course_id' => $course->course_id,
            'day_of_week' => 'Tue',
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'room' => 'LAB-1',
        ]);

        $studentUser = User::factory()->create([
            'name' => 'Grace Hopper',
            'email' => 'ghopper@s.ubaguio.edu',
        ]);

        $student = Student::create([
            'user_id' => $studentUser->user_id,
            'student_number' => '2026111111',
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'department' => 'School of Information Technology',
            'year_level' => 2,
            'program' => 'BSCS',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->delete(route('faculty.courses.destroy', $course));

        $response->assertRedirect(route('faculty.courses.edit', $course));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('enrolled', session('error'));

        $this->assertModelExists($course);
        $this->assertModelExists($schedule);
        $this->assertModelExists($enrollment);
    }

    public function test_faculty_cannot_delete_another_facultys_course(): void
    {
        $otherFacultyUser = User::factory()->faculty()->create([
            'name' => 'Prof. Ada Lovelace',
            'email' => 'alovelace@e.ubaguio.edu',
        ]);

        $otherFaculty = FacultyMember::create([
            'user_id' => $otherFacultyUser->user_id,
            'full_name' => 'Prof. Ada Lovelace',
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);

        $otherCourse = Course::create([
            'faculty_id' => $otherFaculty->faculty_id,
            'school_year_id' => $this->schoolYear->school_year_id,
            'course_name' => 'Discrete Mathematics',
            'subject_code' => 'MATH101',
            'section' => 'M1',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        $response = $this->actingAs($this->facultyUser)
            ->delete(route('faculty.courses.destroy', $otherCourse));

        $response->assertForbidden();
        $this->assertModelExists($otherCourse);
    }

    public function test_course_model_deleting_event_guards_against_enrolled_students(): void
    {
        $course = Course::create([
            'faculty_id' => $this->faculty->faculty_id,
            'school_year_id' => $this->schoolYear->school_year_id,
            'course_name' => 'Operating Systems',
            'subject_code' => 'CS301',
            'section' => 'O1',
            'hours_per_week' => 3,
            'total_semester_hours' => 54,
        ]);

        $studentUser = User::factory()->create();
        $student = Student::create([
            'user_id' => $studentUser->user_id,
            'student_number' => '2026222222',
            'first_name' => 'John',
            'last_name' => 'von Neumann',
            'department' => 'SIT',
            'year_level' => 3,
            'program' => 'BSIT',
        ]);

        Enrollment::create([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Cannot delete subject 'CS301' because students are currently enrolled.");

        $course->delete();
    }
}
