<?php

namespace App\Services;

use App\Models\Course;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\Notification;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AttendanceService
{
    /**
     * Determine the duration in hours for a class session on a given date.
     */
    public function calculateSessionHours(Course $course, CarbonInterface|string $date): float
    {
        $dayOfWeek = Carbon::parse($date)->format('D');
        $schedule = $course->schedules->firstWhere('day_of_week', $dayOfWeek);

        if ($schedule && $schedule->start_time && $schedule->end_time) {
            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);
            $hours = round($start->diffInMinutes($end) / 60.0, 2);

            if ($hours > 0) {
                return $hours;
            }
        }

        $schedulesCount = max(1, $course->schedules->count());

        return round($course->hours_per_week / $schedulesCount, 2);
    }

    /**
     * Check if a given date corresponds to an official meeting day on the course schedule.
     */
    public function isDateOnSchedule(Course $course, CarbonInterface|string $date): bool
    {
        $dayOfWeek = Carbon::parse($date)->format('D');

        return $course->schedules->contains('day_of_week', $dayOfWeek);
    }

    /**
     * Calculate the 20% absence threshold in hours for a course.
     */
    public function getAbsenceThresholdHours(Course $course): float
    {
        return round($course->total_semester_hours * 0.20, 2);
    }

    /**
     * Calculate total absence hours accumulated by an enrolled student.
     */
    public function calculateStudentAbsenceHours(Enrollment $enrollment): float
    {
        $absentRecords = $enrollment->attendanceRecords()
            ->where('status', 'absent')
            ->get();

        $course = $enrollment->course;
        $totalHours = 0.0;

        foreach ($absentRecords as $record) {
            $totalHours += $this->calculateSessionHours($course, $record->session_date);
        }

        return round($totalHours, 2);
    }

    /**
     * Check if student exceeded 20% absence threshold and automatically drop if needed.
     */
    public function evaluateAndApplyDropping(Enrollment $enrollment, int $facultyId): ?DroppingTransaction
    {
        if ($enrollment->status === 'dropped') {
            return null;
        }

        $totalAbsenceHours = $this->calculateStudentAbsenceHours($enrollment);
        $thresholdHours = $this->getAbsenceThresholdHours($enrollment->course);

        if ($totalAbsenceHours > $thresholdHours) {
            $enrollment->update(['status' => 'dropped']);

            $sequence = DroppingTransaction::where('enrollment_id', $enrollment->enrollment_id)->count() + 1;

            $dropTransaction = DroppingTransaction::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'dropped_by' => $facultyId,
                'absence_hours_at_drop' => $totalAbsenceHours,
                'sequence_in_cycle' => $sequence,
                'drop_date' => now()->toDateString(),
            ]);

            if ($enrollment->student_id) {
                Notification::create([
                    'student_id' => $enrollment->student_id,
                    'notification_type' => 'student_dropped',
                    'channel' => 'email',
                    'message' => "You have been dropped from {$enrollment->course->subject_code} ({$enrollment->course->course_name}) for exceeding the 20% absence limit with {$totalAbsenceHours} hours absent (limit: {$thresholdHours} hours).",
                    'sent_at' => now(),
                ]);
            }

            return $dropTransaction;
        }

        return null;
    }
}
