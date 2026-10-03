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
        $activeTab = $request->query('tab', 'check');

        $enrollmentRows = collect();
        $isOnSchedule = true;
        $scheduleDay = Carbon::parse($selectedDate)->format('D');
        $scheduledDays = [];
        $courseStats = [
            'total_students' => 0,
            'sessions_count' => 0,
            'at_risk_count' => 0,
            'dropped_count' => 0,
            'attendance_rate' => 0,
            'threshold_hours' => 0.0,
        ];
        $sessionHistory = collect();

        if ($course) {
            $thresholdHours = $this->attendanceService->getAbsenceThresholdHours($course);
            $scheduledDays = $course->schedules->pluck('day_of_week')->toArray();
            $isOnSchedule = $this->attendanceService->isDateOnSchedule($course, $selectedDate);

            // Load enrollments with student and attendance records
            $enrollments = Enrollment::with(['student.user', 'attendanceRecords'])
                ->where('course_id', $course->course_id)
                ->get();

            $totalPresences = 0;
            $totalRecordedEntries = 0;
            $atRiskCount = 0;
            $droppedCount = 0;

            foreach ($enrollments as $enrollment) {
                $absentHours = $this->attendanceService->calculateStudentAbsenceHours($enrollment);
                $percent = $thresholdHours > 0 ? min(100, round(($absentHours / $thresholdHours) * 100, 1)) : 0;
                $isAtRisk = ($percent >= 70 && $enrollment->status !== 'dropped');

                if ($isAtRisk) {
                    $atRiskCount++;
                }

                if ($enrollment->status === 'dropped') {
                    $droppedCount++;
                }

                $presences = $enrollment->attendanceRecords->where('status', 'present')->count();
                $lates = $enrollment->attendanceRecords->where('status', 'late')->count();
                $absences = $enrollment->attendanceRecords->where('status', 'absent')->count();
                $entriesCount = $enrollment->attendanceRecords->count();

                $totalPresences += $presences;
                $totalRecordedEntries += $entriesCount;

                $todayRecord = $enrollment->attendanceRecords
                    ->first(fn (AttendanceRecord $r) => Carbon::parse($r->session_date)->isSameDay($selectedDate));

                $enrollmentRows->push([
                    'enrollment' => $enrollment,
                    'student' => $enrollment->student,
                    'status' => $enrollment->status,
                    'absent_hours' => $absentHours,
                    'percent_of_threshold' => $percent,
                    'is_at_risk' => $isAtRisk,
                    'presences' => $presences,
                    'lates' => $lates,
                    'absences' => $absences,
                    'total_sessions' => $entriesCount,
                    'today_status' => $todayRecord?->status,
                    'today_remarks' => $todayRecord?->remarks,
                    'today_record_id' => $todayRecord?->attendance_id,
                ]);
            }

            // Calculate distinct session dates recorded
            $enrollmentIds = $enrollments->pluck('enrollment_id');
            $distinctDates = AttendanceRecord::whereIn('enrollment_id', $enrollmentIds)
                ->select('session_date', 'is_out_of_schedule')
                ->distinct()
                ->orderBy('session_date', 'desc')
                ->get();

            foreach ($distinctDates as $dateItem) {
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
                    'total' => $recordsForDate->count(),
                ]);
            }

            $overallRate = $totalRecordedEntries > 0
                ? round(($totalPresences / $totalRecordedEntries) * 100, 1)
                : 100.0;

            $courseStats = [
                'total_students' => $enrollments->count(),
                'sessions_count' => $distinctDates->count(),
                'at_risk_count' => $atRiskCount,
                'dropped_count' => $droppedCount,
                'attendance_rate' => $overallRate,
                'threshold_hours' => $thresholdHours,
            ];
        }

        return view('faculty.dashboard', [
            'faculty' => $faculty,
            'courses' => $courses,
            'selectedCourse' => $course,
            'selectedDate' => $selectedDate,
            'scheduleDay' => $scheduleDay,
            'scheduledDays' => $scheduledDays,
            'isOnSchedule' => $isOnSchedule,
            'enrollmentRows' => $enrollmentRows,
            'courseStats' => $courseStats,
            'sessionHistory' => $sessionHistory,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Store or update attendance records for a class session.
     */
    public function storeAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,course_id'],
            'session_date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,enrollment_id'],
            'attendance.*.status' => ['required', 'in:present,late,absent'],
            'attendance.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var FacultyMember $faculty */
        $faculty = $request->user()->facultyMember;
        $course = Course::with('schedules')->where('course_id', $validated['course_id'])->firstOrFail();

        if ($course->faculty_id !== $faculty->faculty_id) {
            abort(403, 'Unauthorized access to this course.');
        }

        $sessionDate = Carbon::parse($validated['session_date'])->toDateString();
        $isOutOfSchedule = ! $this->attendanceService->isDateOnSchedule($course, $sessionDate);

        $newlyDroppedStudents = [];

        DB::transaction(function () use ($validated, $faculty, $sessionDate, $isOutOfSchedule, &$newlyDroppedStudents): void {
            foreach ($validated['attendance'] as $entry) {
                $enrollment = Enrollment::with('student', 'course')->find($entry['enrollment_id']);

                if (! $enrollment) {
                    continue;
                }

                // US-03: Prevent recording future attendance for students already dropped
                if ($enrollment->status === 'dropped') {
                    continue;
                }

                AttendanceRecord::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->enrollment_id,
                        'session_date' => $sessionDate,
                    ],
                    [
                        'recorded_by' => $faculty->faculty_id,
                        'status' => $entry['status'],
                        'remarks' => $entry['remarks'] ?? null,
                        'is_out_of_schedule' => $isOutOfSchedule,
                    ]
                );

                // Evaluate if absence limit was reached
                $dropTx = $this->attendanceService->evaluateAndApplyDropping($enrollment, $faculty->faculty_id);

                if ($dropTx) {
                    $newlyDroppedStudents[] = $enrollment->student->fullName;
                }
            }
        });

        $message = "Attendance records successfully saved for {$sessionDate}.";

        if (! empty($newlyDroppedStudents)) {
            $studentNames = implode(', ', $newlyDroppedStudents);
            session()->flash('dropped_alert', "Warning: {$studentNames} exceeded the 20% absence threshold and has been automatically flagged as Dropped.");
        }

        return redirect()->route('faculty.dashboard', [
            'course_id' => $course->course_id,
            'date' => $sessionDate,
            'tab' => 'check',
        ])->with('success', $message);
    }

    /**
     * Manually drop a student meeting the required absence percentage.
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
        $threshold = $this->attendanceService->getAbsenceThresholdHours($enrollment->course);

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
                'message' => "You have been dropped from {$enrollment->course->subject_code} by faculty for exceeding the allowable absence hours ({$absenceHours} / {$threshold} hrs).",
                'sent_at' => now(),
            ]);
        }

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
            ->get(['attendance_id', 'session_date', 'status', 'remarks', 'is_out_of_schedule']);

        return response()->json([
            'student_name' => $enrollment->student->fullName,
            'student_number' => $enrollment->student->student_number,
            'course' => "{$enrollment->course->subject_code} - {$enrollment->course->course_name}",
            'records' => $records,
        ]);
    }
}
