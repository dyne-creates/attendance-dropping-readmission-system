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

    public $timestamps = false;

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
            'hours_per_week' => 'decimal:2',
            'total_semester_hours' => 'decimal:2',
        ];
    }

    public function facultyMember(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class, 'faculty_id', 'faculty_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'school_year_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'course_id', 'course_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id', 'course_id');
    }
}
