<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function dashboard(Request $request, AttendanceService $attendanceService): View
    {
        $student = $request->user()->student;
        $guardians = $student?->guardians()->orderBy('guardian_id')->get() ?? collect();
        $guardian = $guardians->first(
            fn (Guardian $guardian): bool => filled($guardian->email)
        ) ?? $guardians->first();
        $needsGuardianEmail = $student !== null && ! $guardians->contains(
            fn (Guardian $guardian): bool => filled($guardian->email)
        );

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
                    'attendanceRecords as excused_count' => function (Builder $query): void {
                        $query->where('status', 'excused');
                    },
                ])
                ->get();

        foreach ($enrollments as $enrollment) {
            $drops = $enrollment->droppingTransactions->sortByDesc('drop_id');
            $latestDrop = $drops->first();

            $enrollment->setAttribute('drop_count', $drops->count());
            $enrollment->setAttribute('latest_drop', $latestDrop);
            $enrollment->setAttribute(
                'overall_score',
                $attendanceService->calculateOverallScore($enrollment->attendanceRecords)
            );

            $absenceHours = $attendanceService->calculateAbsenceHours(
                $enrollment->course,
                $enrollment->attendanceRecords->where('status', 'absent'),
            );
            $maximumAbsenceHours = $attendanceService->maximumAllowableAbsenceHours($enrollment->course);
            $weeklyDropThresholdHours = $attendanceService->weeklyDropThresholdHours($enrollment->course);
            $warningThresholdHours = round($weeklyDropThresholdHours * 0.8, 2);
            $absenceWarning = $absenceHours >= $maximumAbsenceHours
                ? 'limit_reached'
                : ($absenceHours >= $weeklyDropThresholdHours
                    ? 'weekly_drop_eligible'
                    : ($absenceHours >= $warningThresholdHours ? 'approaching_limit' : null));

            $enrollment->setAttribute('absence_hours', $absenceHours);
            $enrollment->setAttribute('maximum_absence_hours', $maximumAbsenceHours);
            $enrollment->setAttribute('weekly_drop_threshold_hours', $weeklyDropThresholdHours);
            $enrollment->setAttribute('absence_warning', $absenceWarning);
        }

        return view('student.dashboard', compact('enrollments', 'guardian', 'needsGuardianEmail'));
    }

    public function storeGuardianEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $student = $request->user()->student;
        abort_unless($student !== null, 404);

        $guardianWithEmail = $student->guardians()
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->exists();

        if ($guardianWithEmail) {
            return redirect()->route('student.dashboard')
                ->with('info', 'A parent or guardian email is already on file.');
        }

        $guardian = $student->guardians()->orderBy('guardian_id')->first();

        if ($guardian === null) {
            $student->guardians()->create(['email' => $validated['email']]);
        } else {
            $guardian->update(['email' => $validated['email']]);
        }

        return redirect()->route('student.dashboard')
            ->with('success', 'Parent or guardian email saved to your profile.');
    }

    public function mail(Request $request): View
    {
        $student = $request->user()->student;

        $requests = $student === null
            ? collect()
            : DB::table('readmission_requests')
                ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
                ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
                ->where('enrollments.student_id', $student->student_id)
                ->select(
                    'readmission_requests.readmission_id',
                    'readmission_requests.attempt_number',
                    'readmission_requests.reason_code',
                    'readmission_requests.reason_details',
                    'readmission_requests.rejection_reason',
                    'readmission_requests.status',
                    'readmission_requests.outcome',
                    'readmission_requests.excused_from',
                    'readmission_requests.excused_through',
                    'readmission_requests.excused_hours_deducted',
                    'readmission_requests.created_at',
                    'readmission_requests.processed_date',
                    'courses.subject_code',
                    'courses.course_name',
                    'courses.section'
                )
                ->orderByDesc('readmission_requests.readmission_id')
                ->get();

        return view('student.mail', compact('requests'));
    }
}
