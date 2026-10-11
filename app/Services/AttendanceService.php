<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    private const SEMESTER_WEEKS = 18;

    private const MAXIMUM_ABSENCE_PERCENTAGE = 0.20;

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
        $absentRecords = $enrollment->relationLoaded('attendanceRecords')
            ? $enrollment->attendanceRecords->where('status', 'absent')
            : $enrollment->attendanceRecords()->where('status', 'absent')->get();

        $course = $enrollment->relationLoaded('course')
            ? $enrollment->course
            : $enrollment->course()->with('schedules')->firstOrFail();

        return $this->calculateAbsenceHours($course, $absentRecords);
    }

    /**
     * Calculate absence hours from records already loaded for an enrollment.
     *
     * @param  Collection<int, AttendanceRecord>  $absentRecords
     */
    public function calculateAbsenceHours(Course $course, iterable $absentRecords): float
    {
        $totalHours = 0.0;

        foreach ($absentRecords as $record) {
            $totalHours += $this->calculateSessionHours($course, $record->session_date);
        }

        return round($totalHours, 2);
    }

    /**
     * Calculate the maximum absence hours allowed for a course in a semester.
     */
    public function maximumAllowableAbsenceHours(Course $course): float
    {
        return round(
            $course->hours_per_week * self::SEMESTER_WEEKS * self::MAXIMUM_ABSENCE_PERCENTAGE,
            2,
        );
    }
}
