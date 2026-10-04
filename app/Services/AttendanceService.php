<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
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

}
