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
    private const MAXIMUM_ABSENCE_PERCENTAGE = 0.20;

    /**
     * Determine the duration in hours for a class session on a given date.
     */
    public function calculateSessionHours(Course $course, CarbonInterface|string $date, ?int $scheduleId = null): float
    {
        $dayOfWeek = Carbon::parse($date)->format('D');
        $schedule = $scheduleId === null
            ? null
            : $course->schedules->firstWhere('schedule_id', $scheduleId);
        $schedule ??= $course->schedules->firstWhere('day_of_week', $dayOfWeek);

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
            if ($record->status === 'absent') {
                $hours = $record->hours_absent !== null
                    ? (float) $record->hours_absent
                    : $this->calculateSessionHours($course, $record->session_date, $record->schedule_id);
                $totalHours += $hours;
            }
        }

        return round($totalHours, 2);
    }

    /**
     * Calculate the Blackboard-style overall attendance score percentage (0 to 100).
     * Formula:
     * - Present = 1.0
     * - Late = 0.5
     * - Absent = 0.0
     * - Excused = exempt (excluded from points earned and points possible)
     */
    public function calculateOverallScore(iterable $attendanceRecords): float
    {
        $earned = 0.0;
        $possible = 0.0;

        foreach ($attendanceRecords as $record) {
            if ($record->status === 'present') {
                $earned += 1.0;
                $possible += 1.0;
            } elseif ($record->status === 'late') {
                $earned += 0.5;
                $possible += 1.0;
            } elseif ($record->status === 'absent') {
                $earned += 0.0;
                $possible += 1.0;
            }
            // 'excused' is excluded from both earned and possible
        }

        if ($possible === 0.0) {
            return 100.0;
        }

        return round(($earned / $possible) * 100, 2);
    }

    /**
     * Calculate the 20% semester absence threshold for a course.
     */
    public function maximumAllowableAbsenceHours(Course $course): float
    {
        return round((float) $course->total_semester_hours * self::MAXIMUM_ABSENCE_PERCENTAGE, 2);
    }

    /**
     * Calculate the weekly absence threshold for an instructor-initiated drop.
     */
    public function weeklyDropThresholdHours(Course $course): float
    {
        return round((float) $course->hours_per_week, 2);
    }

    /**
     * Calculate accumulated absences as a percentage of the semester's course hours.
     */
    public function calculateAbsencePercentage(Course $course, float $absenceHours): float
    {
        $totalSemesterHours = (float) $course->total_semester_hours;

        return $totalSemesterHours > 0
            ? round(($absenceHours / $totalSemesterHours) * 100, 2)
            : 0.0;
    }

    /**
     * Return the absence percentage at which a student reaches the drop limit.
     */
    public function maximumAllowableAbsencePercentage(): float
    {
        return self::MAXIMUM_ABSENCE_PERCENTAGE * 100;
    }
}
