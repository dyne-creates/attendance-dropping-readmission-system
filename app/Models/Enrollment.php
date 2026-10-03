<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $primaryKey = 'enrollment_id';

    protected $fillable = [
        'student_id',
        'course_id',
        // 'enrolled' | 'dropped' | 'readmitted' — defaults to 'enrolled'
        // at the DB level, so this doesn't need to be set manually on create.
        'status',
        // Increments each time a readmission completes, so old drop
        // history is hidden without being deleted (Ma'am Divine's request).
        'cycle_number',
    ];

    /** The enrolled student. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /** The course offering being taken. */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /** All attendance entries logged against this enrollment. */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'enrollment_id', 'enrollment_id');
    }

    /** All drop events for this enrollment (can be more than one per cycle). */
    public function droppingTransactions(): HasMany
    {
        return $this->hasMany(DroppingTransaction::class, 'enrollment_id', 'enrollment_id');
    }
}