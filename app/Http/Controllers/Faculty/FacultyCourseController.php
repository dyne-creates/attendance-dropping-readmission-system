<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Services\SubjectClassListImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $this->assertNoDuplicateScheduleInputs($validated['schedules'] ?? []);
        $this->assertNoRoomScheduleConflicts($validated['schedules'] ?? []);

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
                    'start_time' => $slot['start_time'].':00',
                    'end_time' => $slot['end_time'].':00',
                    'room' => $slot['room'] ?? null,
                ]);
            }
        });

        return redirect()->route('faculty.dashboard')
            ->with('success', 'Subject created successfully.');
    }

    public function importForm(): View
    {
        return view('faculty.courses.import');
    }

    public function importClassList(Request $request, SubjectClassListImportService $importService): RedirectResponse
    {
        $file = $request->file('file');
        if ($file instanceof UploadedFile && ! $file->isValid()) {
            $message = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE => 'The file exceeds the server upload limit. Choose a smaller file or increase upload_max_filesize and restart PHP.',
                UPLOAD_ERR_FORM_SIZE => 'The file exceeds the maximum size allowed by this form.',
                UPLOAD_ERR_PARTIAL => 'The file upload was interrupted. Please select it again and retry.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server is missing its temporary upload folder. Please contact the administrator.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded file. Please contact the administrator.',
                UPLOAD_ERR_EXTENSION => 'A server extension stopped the file upload. Please contact the administrator.',
                default => 'The file could not be uploaded. Please select it again and retry.',
            };

            return back()
                ->withErrors(['file' => $message])
                ->withInput($request->except('file'));
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:5120'],
        ]);

        try {
            $classList = $importService->parse($validated['file']);
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withErrors(['file' => $exception->getMessage()])
                ->withInput();
        }

        $faculty = $request->user()->facultyMember;
        $this->assertNoDuplicateScheduleInputs($classList['schedules']);
        $this->assertNoRoomScheduleConflicts($classList['schedules']);

        $studentsByNumber = Student::with('user')
            ->whereIn('student_number', collect($classList['students'])->pluck('student_number'))
            ->get()
            ->keyBy('student_number');
        $usersByEmail = User::with('student')
            ->whereIn(DB::raw('LOWER(email)'), collect($classList['students'])->pluck('email'))
            ->get()
            ->keyBy(fn (User $user): string => mb_strtolower($user->email));

        foreach ($classList['students'] as $studentData) {
            $existingStudent = $studentsByNumber->get($studentData['student_number']);
            if ($existingStudent !== null) {
                if ($existingStudent->user->role !== 'student') {
                    throw ValidationException::withMessages([
                        'file' => "The existing account for student {$studentData['student_number']} is not a student account.",
                    ]);
                }

                continue;
            }

            $existingUser = $usersByEmail->get($studentData['email']);
            if ($existingUser !== null && $existingUser->role !== 'student') {
                throw ValidationException::withMessages([
                    'file' => "The email {$studentData['email']} already belongs to another account. Resolve the account conflict before importing.",
                ]);
            }
        }

        $schoolYearDates = $this->schoolYearDates($classList['school_year_label'], $classList['semester']);

        $course = DB::transaction(function () use ($classList, $faculty, $schoolYearDates, $studentsByNumber, $usersByEmail): Course {
            $schoolYear = SchoolYear::firstOrCreate(
                [
                    'year_label' => $classList['school_year_label'],
                    'semester' => $classList['semester'],
                ],
                $schoolYearDates ?? [],
            );

            $courseExists = Course::query()
                ->where('faculty_id', $faculty->faculty_id)
                ->where('school_year_id', $schoolYear->school_year_id)
                ->where('subject_code', $classList['subject_code'])
                ->where('section', $classList['section'])
                ->exists();

            if ($courseExists) {
                throw ValidationException::withMessages([
                    'file' => 'This subject code and section already exist for the selected faculty and school year.',
                ]);
            }

            $course = Course::create([
                'faculty_id' => $faculty->faculty_id,
                'school_year_id' => $schoolYear->school_year_id,
                'course_name' => $classList['course_name'],
                'subject_code' => $classList['subject_code'],
                'section' => $classList['section'],
                'hours_per_week' => $classList['hours_per_week'],
                'total_semester_hours' => $classList['total_semester_hours'],
            ]);

            foreach ($classList['schedules'] as $schedule) {
                $course->schedules()->create([
                    ...$schedule,
                    'start_time' => $schedule['start_time'].':00',
                    'end_time' => $schedule['end_time'].':00',
                ]);
            }

            $enrolledStudentIds = [];

            foreach ($classList['students'] as $studentData) {
                $student = $studentsByNumber->get($studentData['student_number']);

                if ($student === null) {
                    $user = $usersByEmail->get($studentData['email']);
                    if ($user === null) {
                        $user = User::create([
                            'name' => $studentData['first_name'].' '.$studentData['last_name'],
                            'role' => 'student',
                            'email' => $studentData['email'],
                            'password' => 'passwords',
                        ]);
                    }

                    $student = $user->student;

                    if ($student === null) {
                        $student = Student::create([
                            'user_id' => $user->user_id,
                            'student_number' => $studentData['student_number'],
                            'first_name' => $studentData['first_name'],
                            'last_name' => $studentData['last_name'],
                            'department' => $classList['department'],
                            'year_level' => $studentData['year_level'],
                            'program' => $studentData['program'],
                        ]);
                    }
                }

                if (isset($enrolledStudentIds[$student->student_id])) {
                    continue;
                }

                $enrolledStudentIds[$student->student_id] = true;

                Enrollment::create([
                    'student_id' => $student->student_id,
                    'course_id' => $course->course_id,
                    'status' => 'enrolled',
                    'cycle_number' => 1,
                ]);
            }

            return $course;
        });

        return redirect()->route('faculty.dashboard', ['course_id' => $course->course_id])
            ->with('success', "Imported {$classList['subject_code']} with ".count($classList['students']).' students.');
    }

    /**
     * @return array{start_date: string, end_date: string}|null
     */
    private function schoolYearDates(string $yearLabel, string $semester): ?array
    {
        if (SchoolYear::query()->where('year_label', $yearLabel)->where('semester', $semester)->exists()) {
            return null;
        }

        if ($yearLabel === '2026-2027' && $semester === '1st Semester') {
            return [
                'start_date' => '2026-08-10',
                'end_date' => '2026-12-18',
                'is_archived' => false,
            ];
        }

        throw ValidationException::withMessages([
            'file' => "The school year {$yearLabel} ({$semester}) is not set up. Add that term before importing this class list.",
        ]);
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
        $this->assertNoDuplicateScheduleInputs($validated['schedules'] ?? []);
        $this->assertNoRoomScheduleConflicts($validated['schedules'] ?? [], $course);

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
                    'start_time' => $slot['start_time'].':00',
                    'end_time' => $slot['end_time'].':00',
                    'room' => $slot['room'] ?? null,
                ]);
            }
        });

        return redirect()->route('faculty.dashboard', ['course_id' => $course->course_id])
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Show a standalone form for adding a schedule slot to an existing subject.
     */
    public function createSchedule(Request $request, Course $course): View
    {
        $this->authorizeOwnership($request, $course);
        $course->load('schedules');

        return view('faculty.courses.schedules.create', compact('course'));
    }

    /**
     * Add a schedule slot without replacing the subject's existing schedule.
     */
    public function storeSchedule(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwnership($request, $course);

        $validated = $request->validate([
            'day_of_week' => ['required', 'string', 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:100'],
        ]);

        $duplicateExists = $course->schedules()
            ->where('day_of_week', $validated['day_of_week'])
            ->where('start_time', $validated['start_time'].':00')
            ->where('end_time', $validated['end_time'].':00')
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'start_time' => 'This subject already has a schedule slot for that day and time.',
            ]);
        }
        $this->assertNoRoomScheduleConflicts([$validated], $course, '');

        $course->schedules()->create([
            ...$validated,
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
        ]);

        return redirect()->route('faculty.dashboard', [
            'course_id' => $course->course_id,
            'tab' => 'meeting',
        ])->with('success', 'Schedule slot added successfully.');
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

    /**
     * Ensure a submitted set of slots does not repeat the same day and time.
     *
     * @param  array<int, array{day_of_week: string, start_time: string, end_time: string}>  $schedules
     */
    private function assertNoDuplicateScheduleInputs(array $schedules): void
    {
        $seenSlots = [];

        foreach ($schedules as $index => $schedule) {
            $slotKey = implode('|', [
                $schedule['day_of_week'],
                $schedule['start_time'],
                $schedule['end_time'],
            ]);

            if (isset($seenSlots[$slotKey])) {
                throw ValidationException::withMessages([
                    "schedules.{$index}.start_time" => 'This subject cannot have duplicate schedule slots for the same day and time.',
                ]);
            }

            $seenSlots[$slotKey] = true;
        }
    }

    /**
     * Reject a room reservation already used by another subject at the exact day and time.
     *
     * @param  array<int, array{day_of_week: string, start_time: string, end_time: string, room?: ?string}>  $schedules
     */
    private function assertNoRoomScheduleConflicts(
        array $schedules,
        ?Course $course = null,
        string $inputKeyPrefix = 'schedules',
    ): void {
        foreach ($schedules as $index => $schedule) {
            $room = trim((string) ($schedule['room'] ?? ''));

            if ($room === '') {
                continue;
            }

            $query = CourseSchedule::query()
                ->where('day_of_week', $schedule['day_of_week'])
                ->where('start_time', $schedule['start_time'].':00')
                ->where('end_time', $schedule['end_time'].':00')
                ->where('room_key', mb_strtolower($room));

            if ($course) {
                $query->where('course_id', '!=', $course->course_id);
            }

            if ($query->exists()) {
                $field = $inputKeyPrefix === '' ? 'room' : "{$inputKeyPrefix}.{$index}.room";

                throw ValidationException::withMessages([
                    $field => 'This room is already booked for that day and time by another subject.',
                ]);
            }
        }
    }
}
