<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'enrollment_id',
        'schedule_id',
        'recorded_by',
        'session_date',
        'grading_period',
        'status',
        'hours_absent',
        'remarks',
        'is_out_of_schedule',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'is_out_of_schedule' => 'boolean',
            'hours_absent' => 'float',
        ];
    }

    /** The enrollment (student + course) this mark belongs to. */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    /** The specific schedule slot this record was taken for (nullable for legacy records). */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CourseSchedule::class, 'schedule_id', 'schedule_id');
    }

    /** The faculty member who recorded this mark. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class, 'recorded_by', 'faculty_id');
    }
}
