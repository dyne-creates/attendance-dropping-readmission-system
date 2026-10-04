<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $primaryKey = 'attendance_id';

    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'recorded_by',
        'session_date',
        'status',
        'remarks',
        'is_out_of_schedule',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'is_out_of_schedule' => 'boolean',
        ];
    }

    /** The enrollment (student + course) this mark belongs to. */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    /** The faculty member who recorded this mark. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class, 'recorded_by', 'faculty_id');
    }
}
