<?php

namespace App\Http\Controllers;

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

    public function dashboard(Request $request): View
    {
        $student = $request->user()->student;

        $enrollments = $student === null
            ? collect()
            : $student->enrollments()
                ->with([
                    'course.schoolYear',
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

            $enrollment->setAttribute(
                'unassigned_attendance_records',
                $enrollment->attendanceRecords->whereNull('grading_period'),
            );
        }

        return view('dashboard.student', compact('enrollments'));
    }
}
