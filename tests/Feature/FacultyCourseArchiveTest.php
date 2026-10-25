<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyCourseArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_can_archive_a_subject_without_deleting_its_records(): void
    {
        [$faculty, $course, $student] = $this->createCourseWithStudent('20260010');
        $schedule = $course->schedules()->create([
            'day_of_week' => 'Mon',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'room' => 'F111',
        ]);
        $enrollment = $course->enrollments()->create([
            'student_id' => $student->student_id,
            'status' => 'enrolled',
            'cycle_number' => 1,
        ]);
        $attendanceRecord = AttendanceRecord::create([
            'enrollment_id' => $enrollment->enrollment_id,
            'schedule_id' => $schedule->schedule_id,
            'recorded_by' => $faculty->faculty_id,
            'session_date' => '2026-10-26',
            'status' => 'present',
            'hours_absent' => 0,
            'is_out_of_schedule' => false,
        ]);

        $response = $this->actingAs($faculty->user)
            ->post(route('faculty.courses.archive', $course));

        $response->assertRedirect(route('faculty.subjects'))
            ->assertSessionHas('success', 'Subject AGLDEV1 was archived. Its student and attendance records have been kept.');
        $this->assertDatabaseHas('courses', [
            'course_id' => $course->course_id,
            'is_archived' => true,
        ]);
        $this->assertModelExists($schedule);
        $this->assertModelExists($enrollment);
        $this->assertModelExists($attendanceRecord);
    }

    public function test_archived_subjects_are_hidden_from_active_lists_and_shown_with_restore_action(): void
    {
        $faculty = $this->createFaculty();
        $activeCourse = $this->createCourse($faculty, 'AGLDEV1');
        $archivedCourse = $this->createCourse($faculty, 'AGLDEV2');
        $archivedCourse->update(['is_archived' => true]);

        $this->actingAs($faculty->user)
            ->get(route('faculty.subjects'))
            ->assertSee('AGLDEV1')
            ->assertDontSee(route('faculty.courses.enrollments.store', $archivedCourse))
            ->assertSee('Archived Classes')
            ->assertSee('AGLDEV2')
            ->assertSee(route('faculty.courses.restore', $archivedCourse));

        $this->actingAs($faculty->user)
            ->get(route('faculty.dashboard', ['course_id' => $archivedCourse->course_id]))
            ->assertDontSee('AGLDEV2');
    }

    public function test_faculty_can_restore_an_archived_subject(): void
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);
        $course->update(['is_archived' => true]);

        $response = $this->actingAs($faculty->user)
            ->post(route('faculty.courses.restore', $course));

        $response->assertRedirect(route('faculty.subjects'))
            ->assertSessionHas('success', 'Subject AGLDEV1 was restored.');
        $this->assertDatabaseHas('courses', [
            'course_id' => $course->course_id,
            'is_archived' => false,
        ]);
        $this->actingAs($faculty->user)
            ->get(route('faculty.subjects'))
            ->assertSee('AGLDEV1')
            ->assertDontSee('Archived Classes');
    }

    public function test_faculty_cannot_archive_another_facultys_subject(): void
    {
        $faculty = $this->createFaculty();
        $otherFaculty = $this->createFaculty();
        $course = $this->createCourse($otherFaculty);

        $this->actingAs($faculty->user)
            ->post(route('faculty.courses.archive', $course))
            ->assertForbidden();

        $this->assertDatabaseHas('courses', [
            'course_id' => $course->course_id,
            'is_archived' => false,
        ]);
    }

    public function test_faculty_cannot_restore_another_facultys_archived_subject(): void
    {
        $faculty = $this->createFaculty();
        $otherFaculty = $this->createFaculty();
        $course = $this->createCourse($otherFaculty);
        $course->update(['is_archived' => true]);

        $this->actingAs($faculty->user)
            ->post(route('faculty.courses.restore', $course))
            ->assertForbidden();

        $this->assertDatabaseHas('courses', [
            'course_id' => $course->course_id,
            'is_archived' => true,
        ]);
    }

    public function test_faculty_cannot_add_students_to_an_archived_subject(): void
    {
        [$faculty, $course, $student] = $this->createCourseWithStudent('20260011');
        $course->update(['is_archived' => true]);

        $this->actingAs($faculty->user)
            ->post(route('faculty.courses.enrollments.store', $course), [
                'student_number' => $student->student_number,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('enrollments', 0);
    }

    /**
     * @return array{FacultyMember, Course, Student}
     */
    private function createCourseWithStudent(string $studentNumber): array
    {
        $faculty = $this->createFaculty();
        $course = $this->createCourse($faculty);
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $user->user_id,
            'student_number' => $studentNumber,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'department' => 'School of Information Technology',
            'year_level' => 1,
            'program' => 'BSIT-SD',
        ]);

        return [$faculty, $course, $student];
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

    private function createCourse(FacultyMember $faculty, string $subjectCode = 'AGLDEV1'): Course
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
            'subject_code' => $subjectCode,
            'course_name' => 'Agile Development',
            'section' => 'IDB1-SD',
            'hours_per_week' => 5,
            'total_semester_hours' => 90,
        ]);
    }
}
