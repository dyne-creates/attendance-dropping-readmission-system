<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyCourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_can_add_an_existing_student_to_their_subject_by_student_number(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);
        $student = $this->createStudent('20260001');

        $response = $this->actingAs($faculty->user)
            ->post(route('faculty.courses.enrollments.store', $course), [
                'student_number' => $student->student_number,
            ]);

        $response->assertRedirect(route('faculty.subjects'))
            ->assertSessionHas('success', 'Student 20260001 added to AGLDEV1.');
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);
    }

    public function test_faculty_can_see_student_id_enrollment_form_for_each_subject(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);

        $response = $this->actingAs($faculty->user)->get(route('faculty.subjects'));

        $response->assertSee('Student ID (student number)')
            ->assertSee(route('faculty.courses.enrollments.store', $course))
            ->assertSee('Add student');
    }

    public function test_faculty_cannot_add_a_student_number_that_does_not_exist(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);

        $response = $this->actingAs($faculty->user)
            ->from(route('faculty.subjects'))
            ->post(route('faculty.courses.enrollments.store', $course), [
                'student_number' => 'not-registered',
            ]);

        $response->assertRedirect(route('faculty.subjects'))
            ->assertSessionHasErrors([
                'student_number' => 'The selected student number is invalid.',
            ]);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_faculty_cannot_add_a_student_to_another_facultys_subject(): void
    {
        $faculty = $this->createFaculty();
        $otherFaculty = $this->createFaculty();
        $course = $this->createCourse($otherFaculty);
        $student = $this->createStudent('20260002');

        $this->actingAs($faculty->user)
            ->post(route('faculty.courses.enrollments.store', $course), [
                'student_number' => $student->student_number,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_student_cannot_add_students_to_a_subject(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);
        $student = $this->createStudent('20260003');

        $this->actingAs($student->user)
            ->post(route('faculty.courses.enrollments.store', $course), [
                'student_number' => $student->student_number,
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_guest_cannot_add_students_to_a_subject(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);
        $student = $this->createStudent('20260005');

        $this->post(route('faculty.courses.enrollments.store', $course), [
            'student_number' => $student->student_number,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_faculty_cannot_create_a_duplicate_enrollment(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);
        $student = $this->createStudent('20260004');
        $course->enrollments()->create([
            'student_id' => $student->student_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);

        $response = $this->actingAs($faculty->user)
            ->from(route('faculty.subjects'))
            ->post(route('faculty.courses.enrollments.store', $course), [
                'student_number' => $student->student_number,
            ]);

        $response->assertRedirect(route('faculty.subjects'))
            ->assertSessionHasErrors([
                'student_number' => 'This student already has an enrollment record for this subject.',
            ]);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_faculty_cannot_bypass_readmission_for_a_dropped_student(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);
        $student = $this->createStudent('20260006');
        $course->enrollments()->create([
            'student_id' => $student->student_id,
            'status' => 'dropped',
            'cycle_number' => 1,
        ]);

        $response = $this->actingAs($faculty->user)
            ->from(route('faculty.subjects'))
            ->post(route('faculty.courses.enrollments.store', $course), [
                'student_number' => $student->student_number,
            ]);

        $response->assertRedirect(route('faculty.subjects'))
            ->assertSessionHasErrors([
                'student_number' => 'This student already has an enrollment record for this subject.',
            ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'status' => 'dropped',
            'cycle_number' => 1,
        ]);
        $this->assertDatabaseCount('enrollments', 1);
    }

    private function createFaculty(): FacultyMember
    {
        $user = User::factory()->faculty()->create();

        return FacultyMember::create([
            'user_id' => $user->user_id,
            'full_name' => $user->name,
            'department' => 'School of Information Technology',
            'suppress_schedule_warnings' => false,
        ]);
    }

    private function createCourse(FacultyMember $faculty): Course
    {
        $schoolYear = SchoolYear::create([
            'year_label' => '2026-2027',
            'semester' => '1st Semester',
            'start_date' => '2026-08-10',
            'end_date' => '2026-12-18',
        ]);

        return Course::create([
            'faculty_id' => $faculty->faculty_id,
            'school_year_id' => $schoolYear->school_year_id,
            'subject_code' => 'AGLDEV1',
            'course_name' => 'Agile Development',
            'section' => 'IDB1-SD',
            'hours_per_week' => 5,
            'total_semester_hours' => 90,
        ]);
    }

    private function createStudent(string $studentNumber): Student
    {
        $user = User::factory()->create(['role' => 'student']);

        return Student::create([
            'user_id' => $user->user_id,
            'student_number' => $studentNumber,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'department' => 'School of Information Technology',
            'year_level' => 1,
            'program' => 'BSIT-SD',
        ]);
    }
}
