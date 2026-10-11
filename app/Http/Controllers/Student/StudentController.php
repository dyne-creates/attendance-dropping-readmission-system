<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    private const GRADING_PERIODS = [
        'first_grading' => 'First Grading',
        'midterms' => 'Midterms',
        'finals' => 'Finals',
    ];

    public function dashboard(Request $request, AttendanceService $attendanceService): View
    {
        $student = $request->user()->student;

        $enrollments = $student === null
            ? collect()
            : $student->enrollments()
                ->with([
                    'course.schoolYear',
                    'course.schedules',
                    'droppingTransactions.readmissionRequest',
                    'attendanceRecords' => function (HasMany $query): void {
                        $query->orderBy('session_date');
                    },
                ])
                ->withCount([
                    'attendanceRecords',
                    'attendanceRecords as present_count' => function (Builder $query): void {
                        $query->where('status', 'present');
                    },
                    'attendanceRecords as absent_count' => function (Builder $query): void {
                        $query->where('status', 'absent');
                    },
                    'attendanceRecords as late_count' => function (Builder $query): void {
                        $query->where('status', 'late');
                    },
                ])
                ->get();

        foreach ($enrollments as $enrollment) {
            $drops = $enrollment->droppingTransactions->sortByDesc('drop_id');
            $latestDrop = $drops->first();

            $enrollment->setAttribute('drop_count', $drops->count());
            $enrollment->setAttribute('latest_drop', $latestDrop);

            $enrollment->setAttribute(
                'grading_periods',
                collect(self::GRADING_PERIODS)
                    ->map(function (string $label, string $key) use ($enrollment): array {
                        $attendanceRecords = $enrollment->attendanceRecords
                            ->where('grading_period', $key);

                        return [
                            'key' => $key,
                            'label' => $label,
                            'attendance_records' => $attendanceRecords,
                            'counts' => [
                                'present' => $attendanceRecords->where('status', 'present')->count(),
                                'absent' => $attendanceRecords->where('status', 'absent')->count(),
                                'late' => $attendanceRecords->where('status', 'late')->count(),
                            ],
                        ];
                    })
                    ->values(),
            );

            $absenceHours = $attendanceService->calculateAbsenceHours(
                $enrollment->course,
                $enrollment->attendanceRecords->where('status', 'absent'),
            );
            $maximumAbsenceHours = $attendanceService->maximumAllowableAbsenceHours($enrollment->course);
            $warningThresholdHours = round($maximumAbsenceHours * 0.8, 2);
            $absenceWarning = $absenceHours >= $maximumAbsenceHours
                ? 'limit_reached'
                : ($absenceHours >= $warningThresholdHours ? 'approaching_limit' : null);

            $enrollment->setAttribute('absence_hours', $absenceHours);
            $enrollment->setAttribute('maximum_absence_hours', $maximumAbsenceHours);
            $enrollment->setAttribute('absence_warning', $absenceWarning);
        }

        return view('student.dashboard', compact('enrollments'));
    }
}
