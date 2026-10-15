<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FacultyCourseController extends Controller
{
    /**
     * Show the form for creating a new subject.
     */
    public function create(Request $request): View
    {
        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;

        $schoolYears = SchoolYear::orderByDesc('school_year_id')->get();

        return view('faculty.courses.create', compact('faculty', 'schoolYears'));
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;

        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'subject_code' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:50'],
            'hours_per_week' => ['required', 'integer', 'min:1', 'max:40'],
            'total_semester_hours' => ['required', 'numeric', 'min:1', 'max:999'],
            'school_year_id' => ['required', 'integer', 'exists:school_years,school_year_id'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'string', 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'],
            'schedules.*.start_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.end_time' => ['required_with:schedules', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $faculty): void {
            $course = Course::create([
                'faculty_id' => $faculty->faculty_id,
                'school_year_id' => $validated['school_year_id'],
                'course_name' => $validated['course_name'],
                'subject_code' => $validated['subject_code'],
                'section' => $validated['section'],
                'hours_per_week' => $validated['hours_per_week'],
                'total_semester_hours' => $validated['total_semester_hours'],
            ]);

            foreach ($validated['schedules'] ?? [] as $slot) {
                $course->schedules()->create([
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'room' => $slot['room'] ?? null,
                ]);
            }
        });

        return redirect()->route('faculty.dashboard')
            ->with('success', 'Subject created successfully.');
    }

    /**
     * Show the form for editing an existing subject.
     */
    public function edit(Request $request, Course $course): View
    {
        $this->authorizeOwnership($request, $course);

        $course->load('schedules');
        $schoolYears = SchoolYear::orderByDesc('school_year_id')->get();

        return view('faculty.courses.edit', compact('course', 'schoolYears'));
    }

    /**
     * Update an existing subject and its schedule slots.
     */
    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwnership($request, $course);

        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'subject_code' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:50'],
            'hours_per_week' => ['required', 'integer', 'min:1', 'max:40'],
            'total_semester_hours' => ['required', 'numeric', 'min:1', 'max:999'],
            'school_year_id' => ['required', 'integer', 'exists:school_years,school_year_id'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'string', 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'],
            'schedules.*.start_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.end_time' => ['required_with:schedules', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $course): void {
            $course->update([
                'school_year_id' => $validated['school_year_id'],
                'course_name' => $validated['course_name'],
                'subject_code' => $validated['subject_code'],
                'section' => $validated['section'],
                'hours_per_week' => $validated['hours_per_week'],
                'total_semester_hours' => $validated['total_semester_hours'],
            ]);

            // Replace all schedule slots with the new set
            $course->schedules()->delete();

            foreach ($validated['schedules'] ?? [] as $slot) {
                $course->schedules()->create([
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'room' => $slot['room'] ?? null,
                ]);
            }
        });

        return redirect()->route('faculty.dashboard', ['course_id' => $course->course_id])
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Delete a subject. Only allowed if the faculty owns it.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwnership($request, $course);

        $course->delete();

        return redirect()->route('faculty.dashboard')
            ->with('success', 'Subject deleted successfully.');
    }

    /**
     * Ensure the authenticated faculty member owns the given course.
     */
    private function authorizeOwnership(Request $request, Course $course): void
    {
        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;

        if ($course->faculty_id !== $faculty->faculty_id) {
            abort(403, 'You do not have permission to manage this subject.');
        }
    }
}
