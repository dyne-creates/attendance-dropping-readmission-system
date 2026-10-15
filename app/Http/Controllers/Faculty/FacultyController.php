<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\Notification;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
        $sessionHours = 1.5;
        $slotsForDay = collect();
        $selectedSchedule = null;

        if ($course) {
            $scheduledDays = $course->schedules->pluck('day_of_week')->toArray();
            $isOnSchedule = $this->attendanceService->isDateOnSchedule($course, $selectedDate);

            // Find all schedule slots that fall on the selected day of week
            $slotsForDay = $course->schedules->filter(
                fn (mixed $s): bool => $s->day_of_week === $scheduleDay
            )->values();

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
                ->where('course_id', $course->course_id)
                ->get();

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

                $todayRecord = $enrollment->attendanceRecords
                    ->first(function (AttendanceRecord $r) use ($selectedDate, $selectedSchedule): bool {
                        $dateMatch = Carbon::parse($r->session_date)->isSameDay($selectedDate);
                        if (! $dateMatch) {
                            return false;
                        }
                        // If the schedule has multiple slots today, match by schedule_id when available
                        if ($selectedSchedule && $r->schedule_id !== null) {
                            return $r->schedule_id === $selectedSchedule->schedule_id;
                        }

                        return true;
                    });

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
                    'status' => $enrollment->status,
                    'absent_hours' => $absentHours,
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

            if (! $dateStrings->contains($selectedDate)) {
                $dateStrings->push($selectedDate);
            }

            $gridDates = $dateStrings->sort()->values()->map(function (string $dateStr) use ($selectedDate, $course): array {
                $carbon = Carbon::parse($dateStr);

                return [
                    'date' => $dateStr,
                    'header_label' => $carbon->format('n/j/y'), // Blackboard format: 1/6/20
                    'full_label' => $carbon->format('M j, Y (D)'),
                    'is_selected' => $dateStr === $selectedDate,
                    'is_today' => $carbon->isToday(),
                    'is_on_schedule' => $this->attendanceService->isDateOnSchedule($course, $dateStr),
                ];
            });

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
            'activeTab' => $activeTab,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'slotsForDay' => $slotsForDay,
            'selectedSchedule' => $selectedSchedule,
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

                AttendanceRecord::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->enrollment_id,
                        'session_date' => $sessionDate,
                        'schedule_id' => $scheduleId,
                    ],
                    [
                        'recorded_by' => $faculty->faculty_id,
                        'status' => $entry['status'],
                        'grading_period' => $validated['grading_period'],
                        'hours_absent' => $hoursAbsent,
                        'remarks' => $entry['remarks'] ?? null,
                        'is_out_of_schedule' => $isOutOfSchedule,
                    ]
                );
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
        $isOutOfSchedule = ! $this->attendanceService->isDateOnSchedule($course, $sessionDate);

        $existingRecord = $enrollment->attendanceRecords->first(function ($r) use ($sessionDate, $scheduleId): bool {
            $dateMatch = Carbon::parse($r->session_date)->isSameDay($sessionDate);
            if (! $dateMatch) {
                return false;
            }

            return $scheduleId === null || $r->schedule_id === $scheduleId;
        });

        $gradingPeriod = $validated['grading_period'] ?? $existingRecord?->grading_period ?? 'first_grading';

        $hoursAbsent = ($validated['status'] === 'absent' && isset($validated['hours_absent']) && is_numeric($validated['hours_absent']))
            ? (float) $validated['hours_absent']
            : null;

        $record = AttendanceRecord::updateOrCreate(
            [
                'enrollment_id' => $enrollment->enrollment_id,
                'session_date' => $sessionDate,
                'schedule_id' => $scheduleId,
            ],
            [
                'recorded_by' => $faculty->faculty_id,
                'status' => $validated['status'],
                'grading_period' => $gradingPeriod,
                'hours_absent' => $hoursAbsent,
                'remarks' => $validated['remarks'] ?? null,
                'is_out_of_schedule' => $isOutOfSchedule,
            ]
        );

        $enrollment->load('attendanceRecords');
        $absentHours = $this->attendanceService->calculateStudentAbsenceHours($enrollment);
        $overallScore = $this->attendanceService->calculateOverallScore($enrollment->attendanceRecords);

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

        $absenceHours = $this->attendanceService->calculateStudentAbsenceHours($enrollment);

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
