<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\Notification;
use App\Services\AttendanceService;
use App\Services\AttendanceImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class FacultyController extends Controller
{
    public function __construct(
        public AttendanceService $attendanceService
    ) {}

    /**
     * Show the faculty dashboard with student attendance checking tools.
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        /** @var FacultyMember $faculty */
        $faculty = $user->facultyMember ?? FacultyMember::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'full_name' => $user->name,
                'department' => 'School of Information Technology',
                'suppress_schedule_warnings' => false,
            ]
        );

        $courses = Course::with(['schoolYear', 'schedules'])
            ->withCount('enrollments')
            ->where('faculty_id', $faculty->faculty_id)
            ->get();

        $selectedCourseId = $request->query('course_id');
        $course = $courses->firstWhere('course_id', (int) $selectedCourseId) ?? $courses->first();

        $selectedDate = $request->query('date', now()->toDateString());
        $activeTab = $request->query('tab', 'meeting');
        if ($activeTab === 'check') {
            $activeTab = 'meeting';
        }
        $prevDate = Carbon::parse($selectedDate)->subDay()->toDateString();
        $nextDate = Carbon::parse($selectedDate)->addDay()->toDateString();

        $enrollmentRows = collect();
        $isOnSchedule = true;
        $scheduleDay = Carbon::parse($selectedDate)->format('D');
        $scheduledDays = [];
        $courseStats = [
            'total_students' => 0,
            'sessions_count' => 0,
            'dropped_count' => 0,
            'attendance_rate' => 0,
        ];
        $sessionHistory = collect();
        $gridDates = collect();
        $meetingHoursByKey = collect();
        $sessionHours = 1.5;
        $slotsForDay = collect();
        $selectedSchedule = null;
        $isSelectedDateCancelled = false;

        if ($course) {
            $scheduledDays = $course->schedules->pluck('day_of_week')->toArray();
            $isOnSchedule = $this->attendanceService->isDateOnSchedule($course, $selectedDate);

            // Find all schedule slots that fall on the selected day of week
            $slotsForDay = $course->schedules->filter(
                fn (mixed $s): bool => $s->day_of_week === $scheduleDay
            )->values();
            $cancelledScheduleIds = DB::table('course_schedule_cancellations')
                ->whereIn('schedule_id', $slotsForDay->pluck('schedule_id'))
                ->whereDate('session_date', $selectedDate)
                ->pluck('schedule_id');
            $isSelectedDateCancelled = $slotsForDay->isNotEmpty()
                && $slotsForDay->every(fn (CourseSchedule $schedule): bool => $cancelledScheduleIds->contains($schedule->schedule_id));
            $slotsForDay = $slotsForDay
                ->reject(fn (CourseSchedule $schedule): bool => $cancelledScheduleIds->contains($schedule->schedule_id))
                ->values();

            // Determine the active slot: prefer query param, otherwise first for the day
            $selectedScheduleId = (int) $request->query('schedule_id', 0);
            $selectedSchedule = $slotsForDay->firstWhere('schedule_id', $selectedScheduleId)
                ?? $slotsForDay->first();

            $sessionHours = ($selectedSchedule && $selectedSchedule->start_time && $selectedSchedule->end_time)
                ? round(
                    Carbon::parse($selectedSchedule->start_time)->diffInMinutes(Carbon::parse($selectedSchedule->end_time)) / 60.0,
                    2
                )
                : $this->attendanceService->calculateSessionHours($course, $selectedDate);

            // Load enrollments with student and attendance records
            $enrollments = Enrollment::with(['student.user', 'attendanceRecords'])
                ->withCount('droppingTransactions')
                ->where('course_id', $course->course_id)
                ->get();

            $maximumAbsenceHours = $this->attendanceService->maximumAllowableAbsenceHours($course);
            $totalPresences = 0;
            $totalRecordedEntries = 0;
            $droppedCount = 0;

            foreach ($enrollments as $enrollment) {
                $absentHours = $this->attendanceService->calculateStudentAbsenceHours($enrollment);
                if ($enrollment->status === 'dropped') {
                    $droppedCount++;
                }

                $presences = $enrollment->attendanceRecords->where('status', 'present')->count();
                $lates = $enrollment->attendanceRecords->where('status', 'late')->count();
                $absences = $enrollment->attendanceRecords->where('status', 'absent')->count();
                $excusedCount = $enrollment->attendanceRecords->where('status', 'excused')->count();
                $entriesCount = $enrollment->attendanceRecords->count();

                $totalPresences += $presences;
                $totalRecordedEntries += $entriesCount;

                $recordsForSelectedDate = $enrollment->attendanceRecords
                    ->filter(fn (AttendanceRecord $record): bool => Carbon::parse($record->session_date)->isSameDay($selectedDate));
                $todayRecord = $selectedSchedule
                    ? $recordsForSelectedDate->first(fn (AttendanceRecord $record): bool => $record->schedule_id === $selectedSchedule->schedule_id)
                        ?? $recordsForSelectedDate->first(fn (AttendanceRecord $record): bool => $record->schedule_id === null)
                    : $recordsForSelectedDate->first(fn (AttendanceRecord $record): bool => $record->schedule_id === null)
                        ?? $recordsForSelectedDate->first();

                $overallScore = $this->attendanceService->calculateOverallScore($enrollment->attendanceRecords);

                // Key records by "date|scheduleId" so the overall grid can distinguish multi-slot days
                $recordsByDate = $enrollment->attendanceRecords
                    ->keyBy(fn (AttendanceRecord $r): string => Carbon::parse($r->session_date)->toDateString().'|'.($r->schedule_id ?? ''))
                    ->map(fn (AttendanceRecord $r): array => [
                        'attendance_id' => $r->attendance_id,
                        'status' => $r->status,
                        'hours_absent' => $r->hours_absent,
                        'remarks' => $r->remarks,
                        'is_out_of_schedule' => (bool) $r->is_out_of_schedule,
                        'grading_period' => $r->grading_period,
                        'schedule_id' => $r->schedule_id,
                    ]);

                $enrollmentRows->push([
                    'enrollment' => $enrollment,
                    'student' => $enrollment->student,
                    'drop_count' => $enrollment->dropping_transactions_count,
                    'status' => $enrollment->status,
                    'absent_hours' => $absentHours,
                    'maximum_absence_hours' => $maximumAbsenceHours,
                    'absence_percentage' => $this->attendanceService->calculateAbsencePercentage($course, $absentHours),
                    'maximum_absence_percentage' => $this->attendanceService->maximumAllowableAbsencePercentage(),
                    'is_drop_eligible' => $enrollment->status !== 'dropped' && $absentHours >= $maximumAbsenceHours,
                    'presences' => $presences,
                    'lates' => $lates,
                    'absences' => $absences,
                    'excused' => $excusedCount,
                    'total_sessions' => $entriesCount,
                    'overall_score' => $overallScore,
                    'today_status' => $todayRecord?->status,
                    'today_hours_absent' => $todayRecord?->hours_absent ?? $sessionHours,
                    'today_remarks' => $todayRecord?->remarks,
                    'today_record_id' => $todayRecord?->attendance_id,
                    'today_grading_period' => $todayRecord?->grading_period,
                    'records_by_date' => $recordsByDate,
                ]);
            }

            // Calculate distinct session dates recorded
            $enrollmentIds = $enrollments->pluck('enrollment_id');
            $distinctDates = AttendanceRecord::whereIn('enrollment_id', $enrollmentIds)
                ->select('session_date', 'is_out_of_schedule')
                ->distinct()
                ->orderBy('session_date', 'asc')
                ->get();

            $dateStrings = $distinctDates->pluck('session_date')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())
                ->unique();

            $cancelledOccurrenceKeys = DB::table('course_schedule_cancellations')
                ->whereIn('schedule_id', $course->schedules->pluck('schedule_id'))
                ->get(['schedule_id', 'session_date'])
                ->mapWithKeys(fn (object $cancellation): array => [
                    Carbon::parse($cancellation->session_date)->toDateString().'|'.$cancellation->schedule_id => true,
                ]);

            if (! $dateStrings->contains($selectedDate)) {
                $selectedDaySlots = $course->schedules
                    ->filter(fn (CourseSchedule $schedule): bool => $schedule->day_of_week === Carbon::parse($selectedDate)->format('D'));
                $allSelectedDaySlotsCancelled = $selectedDaySlots->isNotEmpty()
                    && $selectedDaySlots->every(fn (CourseSchedule $schedule): bool => $cancelledOccurrenceKeys->has($selectedDate.'|'.$schedule->schedule_id));

                if (! $allSelectedDaySlotsCancelled) {
                    $dateStrings->push($selectedDate);
                }
            }

            $gridDates = $dateStrings->sort()->values()->flatMap(function (string $dateStr) use ($selectedDate, $course, $enrollments, $cancelledOccurrenceKeys): Collection {
                $carbon = Carbon::parse($dateStr);
                $scheduledSlots = $course->schedules
                    ->filter(fn (CourseSchedule $schedule): bool => $schedule->day_of_week === $carbon->format('D')
                        && ! $cancelledOccurrenceKeys->has($dateStr.'|'.$schedule->schedule_id));
                $recordedScheduleIds = $enrollments
                    ->flatMap(fn (Enrollment $enrollment) => $enrollment->attendanceRecords
                        ->filter(fn (AttendanceRecord $record): bool => Carbon::parse($record->session_date)->toDateString() === $dateStr)
                        ->filter(fn (AttendanceRecord $record): bool => $record->schedule_id === null
                            || ! $cancelledOccurrenceKeys->has($dateStr.'|'.$record->schedule_id))
                        ->pluck('schedule_id'))
                    ->map(fn ($scheduleId) => $scheduleId === null ? null : (int) $scheduleId)
                    ->unique()
                    ->values();
                $scheduleIds = $scheduledSlots->pluck('schedule_id')
                    ->merge($recordedScheduleIds->filter(fn ($scheduleId): bool => $scheduleId !== null))
                    ->map(fn ($scheduleId): int => (int) $scheduleId)
                    ->unique()
                    ->values();

                if ($recordedScheduleIds->contains(null) || $scheduleIds->isEmpty()) {
                    $scheduleIds->push(null);
                }

                return $scheduleIds->map(function (?int $scheduleId) use ($carbon, $course, $dateStr, $selectedDate): array {
                    $schedule = $course->schedules->firstWhere('schedule_id', $scheduleId);
                    $scheduleLabel = $schedule
                        ? Carbon::parse($schedule->start_time)->format('g:i A').' – '.Carbon::parse($schedule->end_time)->format('g:i A')
                        : null;
                    $sessionHours = $schedule
                        ? round(Carbon::parse($schedule->start_time)->diffInMinutes(Carbon::parse($schedule->end_time)) / 60.0, 2)
                        : $this->attendanceService->calculateSessionHours($course, $dateStr);

                    return [
                        'date' => $dateStr,
                        'column_key' => $dateStr.'|'.($scheduleId ?? ''),
                        'schedule_id' => $scheduleId,
                        'schedule_label' => $scheduleLabel,
                        'session_hours' => $sessionHours,
                        'header_label' => $carbon->format('n/j/y'),
                        'full_label' => $carbon->format('M j, Y'),
                        'is_selected' => $dateStr === $selectedDate,
                        'is_today' => $carbon->isToday(),
                        'is_on_schedule' => $this->attendanceService->isDateOnSchedule($course, $dateStr),
                    ];
                });
            });
            $meetingHoursByKey = $gridDates->mapWithKeys(
                fn (array $meeting): array => [$meeting['column_key'] => $meeting['session_hours']],
            );

            foreach ($distinctDates->sortByDesc('session_date') as $dateItem) {
                $sessionDate = Carbon::parse($dateItem->session_date)->toDateString();
                $recordsForDate = AttendanceRecord::whereIn('enrollment_id', $enrollmentIds)
                    ->whereDate('session_date', $sessionDate)
                    ->get();

                $sessionHistory->push([
                    'session_date' => $sessionDate,
                    'day_of_week' => Carbon::parse($sessionDate)->format('l, M d, Y'),
                    'is_out_of_schedule' => (bool) $dateItem->is_out_of_schedule,
                    'present_count' => $recordsForDate->where('status', 'present')->count(),
                    'late_count' => $recordsForDate->where('status', 'late')->count(),
                    'absent_count' => $recordsForDate->where('status', 'absent')->count(),
                    'excused_count' => $recordsForDate->where('status', 'excused')->count(),
                    'total' => $recordsForDate->count(),
                ]);
            }

            $overallRate = $totalRecordedEntries > 0
                ? round(($totalPresences / $totalRecordedEntries) * 100, 1)
                : 100.0;

            $courseStats = [
                'total_students' => $enrollments->count(),
                'sessions_count' => $distinctDates->count(),
                'dropped_count' => $droppedCount,
                'attendance_rate' => $overallRate,
            ];
        }

        return view('faculty.dashboard', [
            'faculty' => $faculty,
            'courses' => $courses,
            'selectedCourse' => $course,
            'selectedDate' => $selectedDate,
            'sessionHours' => $sessionHours,
            'scheduleDay' => $scheduleDay,
            'scheduledDays' => $scheduledDays,
            'isOnSchedule' => $isOnSchedule,
            'enrollmentRows' => $enrollmentRows,
            'courseStats' => $courseStats,
            'sessionHistory' => $sessionHistory,
            'gridDates' => $gridDates,
            'meetingHoursByKey' => $meetingHoursByKey,
            'activeTab' => $activeTab,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'slotsForDay' => $slotsForDay,
            'selectedSchedule' => $selectedSchedule,
            'isSelectedDateCancelled' => $isSelectedDateCancelled,
        ]);
    }

    /**
     * Store or update attendance records for a class session.
     */
    public function storeAttendance(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,course_id'],
            'session_date' => ['required', 'date'],
            'schedule_id' => ['nullable', 'integer', 'exists:course_schedules,schedule_id'],
            'grading_period' => ['required', 'in:first_grading,midterms,finals'],
            'attendance' => ['required', 'array'],
            'attendance.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,enrollment_id'],
            'attendance.*.status' => ['required', 'in:present,late,absent,excused'],
            'attendance.*.hours_absent' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'attendance.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;
        $course = Course::with('schedules')->where('course_id', $validated['course_id'])->firstOrFail();

        if ($course->faculty_id !== $faculty->faculty_id) {
            abort(403, 'Unauthorized access to this course.');
        }

        $sessionDate = Carbon::parse($validated['session_date'])->toDateString();
        $scheduleId = $validated['schedule_id'] ?? null;
        if ($scheduleId !== null) {
            $schedule = $this->scheduleForCourse($course, (int) $scheduleId);

            $this->ensureScheduleOccurrenceAvailable($schedule, $sessionDate);
        } else {
            $this->ensureDateIsNotFullyCancelled($course, $sessionDate);
        }
        $isOutOfSchedule = ! $this->attendanceService->isDateOnSchedule($course, $sessionDate);

        DB::transaction(function () use ($validated, $faculty, $sessionDate, $scheduleId, $isOutOfSchedule): void {
            foreach ($validated['attendance'] as $entry) {
                $enrollment = Enrollment::with('student', 'course')->find($entry['enrollment_id']);

                if (! $enrollment) {
                    continue;
                }

                // US-03: Prevent recording future attendance for students already dropped
                if ($enrollment->status === 'dropped') {
                    continue;
                }

                $hoursAbsent = ($entry['status'] === 'absent' && isset($entry['hours_absent']) && is_numeric($entry['hours_absent']))
                    ? (float) $entry['hours_absent']
                    : null;

                $recordQuery = AttendanceRecord::where('enrollment_id', $enrollment->enrollment_id)
                    ->whereDate('session_date', $sessionDate);
                $existingRecord = (clone $recordQuery)->where('schedule_id', $scheduleId)->first();

                if (! $existingRecord && $scheduleId !== null) {
                    $existingRecord = (clone $recordQuery)->whereNull('schedule_id')->first();
                }

                $attributes = [
                    'schedule_id' => $scheduleId,
                    'recorded_by' => $faculty->faculty_id,
                    'status' => $entry['status'],
                    'grading_period' => $validated['grading_period'],
                    'hours_absent' => $hoursAbsent,
                    'remarks' => $entry['remarks'] ?? null,
                    'is_out_of_schedule' => $isOutOfSchedule,
                ];

                if ($existingRecord) {
                    $existingRecord->update($attributes);
                } else {
                    AttendanceRecord::create([
                        'enrollment_id' => $enrollment->enrollment_id,
                        'session_date' => $sessionDate,
                        ...$attributes,
                    ]);
                }
            }
        });

        $message = "Attendance records successfully saved for {$sessionDate}.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('faculty.dashboard', [
            'course_id' => $course->course_id,
            'date' => $sessionDate,
            'schedule_id' => $scheduleId,
            'tab' => $request->input('tab', 'check'),
        ])->with('success', $message);
    }

    /**
     * Import attendance from a date-column CSV or Excel workbook.
     */
    public function importAttendance(Request $request, AttendanceImportService $importService): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,course_id'],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'extensions:csv,xlsx', 'max:5120'],
            'grading_period' => ['required', 'in:first_grading,midterms,finals'],
        ]);

        $faculty = $request->user()->facultyMember;
        $course = Course::with('schedules')->where('course_id', $validated['course_id'])->firstOrFail();
        abort_unless($course->faculty_id === $faculty?->faculty_id, 403, 'Unauthorized access to this course.');

        try {
            $rows = $importService->rows($validated['file']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()])->withInput();
        }

        $dates = collect($rows)->pluck('meeting_date')->unique()->values();
        foreach ($dates as $sessionDate) {
            $this->ensureDateIsNotFullyCancelled($course, $sessionDate);
        }

        $studentNumbers = collect($rows)->pluck('student_number')->unique()->values();
        $attendanceKeys = collect($rows)->map(fn (array $row): string => $row['meeting_date'].'|'.$row['student_number']);
        if ($studentNumbers->contains('') || $attendanceKeys->count() !== $attendanceKeys->unique()->count()) {
            return back()->withErrors(['file' => 'Each student can have only one attendance mark per meeting date.'])->withInput();
        }

        $enrollmentsByStudentNumber = Enrollment::with('student')
            ->where('course_id', $course->course_id)
            ->whereHas('student', fn ($query) => $query->whereIn('student_number', $studentNumbers))
            ->get()
            ->keyBy(fn (Enrollment $enrollment): string => $enrollment->student->student_number);

        foreach ($rows as $row) {
            $enrollment = $enrollmentsByStudentNumber->get($row['student_number']);
            if (! in_array($row['status'], ['present', 'absent', 'late', 'excused'], true)) {
                return back()->withErrors(['file' => "Row {$row['row']} has an invalid status. Use A, P, L, or E."])->withInput();
            }
            if (! $enrollment) {
                return back()->withErrors(['file' => "Student {$row['student_number']} on row {$row['row']} is not enrolled in this course."])->withInput();
            }
            if ($enrollment->status === 'dropped') {
                return back()->withErrors(['file' => "Student {$row['student_number']} on row {$row['row']} has been dropped from this course."])->withInput();
            }
        }

        $isOutOfScheduleByDate = $dates->mapWithKeys(fn (string $date): array => [
            $date => ! $this->attendanceService->isDateOnSchedule($course, $date),
        ]);

        DB::transaction(function () use ($rows, $enrollmentsByStudentNumber, $faculty, $isOutOfScheduleByDate, $validated): void {
            foreach ($rows as $row) {
                $enrollment = $enrollmentsByStudentNumber->get($row['student_number']);
                $recordQuery = AttendanceRecord::where('enrollment_id', $enrollment->enrollment_id)
                    ->whereDate('session_date', $row['meeting_date'])
                    ->whereNull('schedule_id');
                $existingRecord = (clone $recordQuery)->first();

                $attributes = [
                    'schedule_id' => null,
                    'recorded_by' => $faculty->faculty_id,
                    'grading_period' => $validated['grading_period'],
                    'status' => $row['status'],
                    'hours_absent' => null,
                    'remarks' => $row['remarks'],
                    'is_out_of_schedule' => $isOutOfScheduleByDate->get($row['meeting_date']),
                ];

                if ($existingRecord) {
                    $existingRecord->update($attributes);
                } else {
                    AttendanceRecord::create([
                        'enrollment_id' => $enrollment->enrollment_id,
                        'session_date' => $row['meeting_date'],
                        ...$attributes,
                    ]);
                }
            }
        });

        $latestMeetingDate = $dates->sort()->last();

        return redirect()->route('faculty.dashboard', [
            'course_id' => $course->course_id,
            'date' => $latestMeetingDate,
            'tab' => 'meeting',
        ])->with('success', count($rows).' attendance marks imported across '.count($dates).' meeting dates.');
    }

    public function clearMeeting(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,course_id'],
            'session_date' => ['required', 'date'],
            'schedule_id' => ['present', 'nullable', 'integer', 'exists:course_schedules,schedule_id'],
        ]);

        $course = Course::query()->findOrFail($validated['course_id']);
        $this->authorizeFacultyCourse($request, $course);
        $scheduleId = $validated['schedule_id'];

        if ($scheduleId !== null) {
            $schedule = $this->scheduleForCourse($course, (int) $scheduleId);
            abort_unless(
                $schedule->day_of_week === Carbon::parse($validated['session_date'])->format('D'),
                422,
                'The schedule does not match the meeting date.',
            );
        }

        $enrollmentIds = Enrollment::query()
            ->where('course_id', $course->course_id)
            ->select('enrollment_id');
        $records = AttendanceRecord::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereDate('session_date', Carbon::parse($validated['session_date'])->toDateString());
        $scheduleId === null
            ? $records->whereNull('schedule_id')
            : $records->where('schedule_id', $scheduleId);
        $deletedCount = $records->delete();

        return back()->with('success', "{$deletedCount} attendance marks cleared for the selected meeting.");
    }

    public function deleteMeeting(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,course_id'],
            'session_date' => ['required', 'date'],
            'schedule_id' => ['required', 'integer', 'exists:course_schedules,schedule_id'],
        ]);

        $course = Course::query()->findOrFail($validated['course_id']);
        $this->authorizeFacultyCourse($request, $course);
        $schedule = $this->scheduleForCourse($course, (int) $validated['schedule_id']);
        $sessionDate = Carbon::parse($validated['session_date'])->toDateString();

        abort_unless($schedule->day_of_week === Carbon::parse($sessionDate)->format('D'), 422, 'The schedule does not match the meeting date.');

        DB::transaction(function () use ($request, $course, $schedule, $sessionDate): void {
            $enrollmentIds = Enrollment::query()
                ->where('course_id', $course->course_id)
                ->select('enrollment_id');
            AttendanceRecord::query()
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereDate('session_date', $sessionDate)
                ->where('schedule_id', $schedule->schedule_id)
                ->delete();

            DB::table('course_schedule_cancellations')->updateOrInsert(
                ['schedule_id' => $schedule->schedule_id, 'session_date' => $sessionDate],
                [
                    'cancelled_by' => $request->user()->facultyMember->faculty_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });

        return back()->with('success', 'The selected dated meeting was deleted. The recurring weekly schedule remains unchanged.');
    }

    private function authorizeFacultyCourse(Request $request, Course $course): void
    {
        abort_unless($course->faculty_id === $request->user()->facultyMember?->faculty_id, 403, 'Unauthorized access to this course.');
    }

    private function scheduleForCourse(Course $course, int $scheduleId): CourseSchedule
    {
        return CourseSchedule::query()
            ->where('course_id', $course->course_id)
            ->findOrFail($scheduleId);
    }

    private function ensureScheduleOccurrenceAvailable(CourseSchedule $schedule, string $sessionDate): void
    {
        abort_unless(
            ! DB::table('course_schedule_cancellations')
                ->where('schedule_id', $schedule->schedule_id)
                ->whereDate('session_date', $sessionDate)
                ->exists(),
            422,
            'The selected dated meeting has been cancelled.',
        );
    }

    private function ensureDateIsNotFullyCancelled(Course $course, string $sessionDate): void
    {
        $scheduledSlots = $course->schedules
            ->filter(fn (CourseSchedule $schedule): bool => $schedule->day_of_week === Carbon::parse($sessionDate)->format('D'));

        abort_unless(
            $scheduledSlots->isEmpty()
                || ! $scheduledSlots->every(fn (CourseSchedule $schedule): bool => DB::table('course_schedule_cancellations')
                    ->where('schedule_id', $schedule->schedule_id)
                    ->whereDate('session_date', $sessionDate)
                    ->exists()),
            422,
            'All scheduled meetings on this date have been cancelled.',
        );
    }

    /**
     * Record or update attendance for a single student on a given date (used by Blackboard grid).
     */
    public function recordSingleAttendance(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,course_id'],
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,enrollment_id'],
            'session_date' => ['required', 'date'],
            'status' => ['required', 'in:present,late,absent,excused'],
            'hours_absent' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'grading_period' => ['nullable', 'in:first_grading,midterms,finals'],
            'schedule_id' => ['nullable', 'integer', 'exists:course_schedules,schedule_id'],
        ]);

        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;
        $course = Course::with('schedules')->where('course_id', $validated['course_id'])->firstOrFail();

        if ($course->faculty_id !== $faculty->faculty_id) {
            abort(403, 'Unauthorized access to this course.');
        }

        $enrollment = Enrollment::with('student', 'attendanceRecords')->where('course_id', $course->course_id)
            ->where('enrollment_id', $validated['enrollment_id'])
            ->firstOrFail();

        if ($enrollment->status === 'dropped') {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Cannot record attendance for a dropped student.'], 422);
            }

            return back()->with('error', 'Cannot record attendance for a dropped student.');
        }

        $sessionDate = Carbon::parse($validated['session_date'])->toDateString();
        $scheduleId = $validated['schedule_id'] ?? null;
        if ($scheduleId !== null) {
            $schedule = $this->scheduleForCourse($course, (int) $scheduleId);

            $this->ensureScheduleOccurrenceAvailable($schedule, $sessionDate);
        } else {
            $this->ensureDateIsNotFullyCancelled($course, $sessionDate);
        }
        $isOutOfSchedule = ! $this->attendanceService->isDateOnSchedule($course, $sessionDate);

        $existingRecord = $enrollment->attendanceRecords->first(function ($r) use ($sessionDate, $scheduleId): bool {
            $dateMatch = Carbon::parse($r->session_date)->isSameDay($sessionDate);

            return $dateMatch && $r->schedule_id === $scheduleId;
        }) ?? ($scheduleId !== null
            ? $enrollment->attendanceRecords->first(fn (AttendanceRecord $r): bool => Carbon::parse($r->session_date)->isSameDay($sessionDate) && $r->schedule_id === null)
            : null);

        $gradingPeriod = $validated['grading_period'] ?? $existingRecord?->grading_period ?? 'first_grading';

        $hoursAbsent = ($validated['status'] === 'absent' && isset($validated['hours_absent']) && is_numeric($validated['hours_absent']))
            ? (float) $validated['hours_absent']
            : null;

        $attributes = [
            'schedule_id' => $scheduleId,
            'recorded_by' => $faculty->faculty_id,
            'status' => $validated['status'],
            'grading_period' => $gradingPeriod,
            'hours_absent' => $hoursAbsent,
            'remarks' => $validated['remarks'] ?? null,
            'is_out_of_schedule' => $isOutOfSchedule,
        ];
        if ($existingRecord) {
            $existingRecord->update($attributes);
            $record = $existingRecord->refresh();
        } else {
            $record = AttendanceRecord::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'session_date' => $sessionDate,
                ...$attributes,
            ]);
        }

        $enrollment->load('attendanceRecords');
        $absentHours = $this->attendanceService->calculateStudentAbsenceHours($enrollment);
        $overallScore = $this->attendanceService->calculateOverallScore($enrollment->attendanceRecords);
        $maximumAbsenceHours = $this->attendanceService->maximumAllowableAbsenceHours($course);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance saved.',
                'record' => [
                    'attendance_id' => $record->attendance_id,
                    'status' => $record->status,
                    'hours_absent' => $record->hours_absent,
                    'remarks' => $record->remarks,
                    'session_date' => $record->session_date->toDateString(),
                    'schedule_id' => $record->schedule_id,
                ],
                'stats' => [
                    'overall_score' => $overallScore,
                    'absent_hours' => $absentHours,
                    'maximum_absence_hours' => $maximumAbsenceHours,
                    'absence_percentage' => $this->attendanceService->calculateAbsencePercentage($course, $absentHours),
                    'maximum_absence_percentage' => $this->attendanceService->maximumAllowableAbsencePercentage(),
                    'is_drop_eligible' => $absentHours >= $maximumAbsenceHours,
                    'presences' => $enrollment->attendanceRecords->where('status', 'present')->count(),
                    'lates' => $enrollment->attendanceRecords->where('status', 'late')->count(),
                    'absences' => $enrollment->attendanceRecords->where('status', 'absent')->count(),
                    'excused' => $enrollment->attendanceRecords->where('status', 'excused')->count(),
                ],
            ]);
        }

        return back()->with('success', 'Attendance record updated.');
    }

    /**
     * Drop a student from a course at the faculty member's discretion.
     */
    public function dropStudent(Request $request, Enrollment $enrollment): RedirectResponse
    {
        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;

        if ($enrollment->course->faculty_id !== $faculty->faculty_id) {
            abort(403, 'Unauthorized access to this student enrollment.');
        }

        if ($enrollment->status === 'dropped') {
            return back()->with('info', 'This student is already marked as Dropped.');
        }

        $enrollment->loadMissing('course.schedules', 'attendanceRecords');
        $absenceHours = $this->attendanceService->calculateStudentAbsenceHours($enrollment);
        $maximumAbsenceHours = $this->attendanceService->maximumAllowableAbsenceHours($enrollment->course);

        if ($absenceHours < $maximumAbsenceHours) {
            return back()->with('error', sprintf(
                'This student can be dropped after reaching %.2f absent hours; current total is %.2f hours.',
                $maximumAbsenceHours,
                $absenceHours,
            ));
        }

        DB::transaction(function () use ($enrollment, $faculty, $absenceHours): void {
            $enrollment->update(['status' => 'dropped']);

            $sequence = DroppingTransaction::where('enrollment_id', $enrollment->enrollment_id)->count() + 1;

            DroppingTransaction::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'dropped_by' => $faculty->faculty_id,
                'absence_hours_at_drop' => $absenceHours,
                'sequence_in_cycle' => $sequence,
                'drop_date' => now()->toDateString(),
            ]);

            if ($enrollment->student_id) {
                Notification::create([
                    'student_id' => $enrollment->student_id,
                    'notification_type' => 'student_dropped',
                    'channel' => 'email',
                    'message' => "You have been dropped from {$enrollment->course->subject_code} by your faculty member. Contact your faculty member for more information.",
                    'sent_at' => now(),
                ]);
            }
        });

        return back()->with('success', "Student {$enrollment->student->fullName} has been dropped from the course.");
    }

    /**
     * Toggle the suppression of out-of-schedule warnings.
     */
    public function toggleScheduleWarning(Request $request): RedirectResponse
    {
        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;
        $faculty->update([
            'suppress_schedule_warnings' => ! $faculty->suppress_schedule_warnings,
        ]);

        $status = $faculty->suppress_schedule_warnings ? 'suppressed' : 'enabled';

        return back()->with('info', "Out-of-schedule day warnings have been {$status}.");
    }

    /**
     * Get attendance history for a single student enrollment.
     */
    public function studentAttendanceHistory(Request $request, Enrollment $enrollment): JsonResponse
    {
        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;

        if ($enrollment->course->faculty_id !== $faculty->faculty_id) {
            abort(403, 'Unauthorized access.');
        }

        $records = $enrollment->attendanceRecords()
            ->orderBy('session_date', 'desc')
            ->get(['attendance_id', 'session_date', 'status', 'hours_absent', 'remarks', 'is_out_of_schedule']);

        return response()->json([
            'student_name' => $enrollment->student->fullName,
            'student_number' => $enrollment->student->student_number,
            'course' => "{$enrollment->course->subject_code} - {$enrollment->course->course_name}",
            'records' => $records,
        ]);
    }
}
