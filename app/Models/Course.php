<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $primaryKey = 'course_id';

    protected $fillable = [
        'faculty_id',
        'school_year_id',
        'subject_code',
        'course_name',
        'section',
        'hours_per_week',
        'total_semester_hours',
    ];

    protected function casts(): array
    {
        return [
            'hours_per_week' => 'integer',
            'total_semester_hours' => 'decimal:2',
        ];
    }

    /** The faculty member teaching this course. */
    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class, 'faculty_id', 'faculty_id');
    }

    /** The school year this offering belongs to. */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'school_year_id');
    }

    /** Weekly meeting slots for this course (used for the schedule-warning check). */
    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'course_id', 'course_id');
    }

    /** Students enrolled in this course offering. */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id', 'course_id');
    }
}