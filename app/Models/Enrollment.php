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

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'course_id',
        'status',
        'cycle_number',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'enrollment_id', 'enrollment_id');
    }

    public function droppingTransactions(): HasMany
    {
        return $this->hasMany(DroppingTransaction::class, 'enrollment_id', 'enrollment_id');
    }
}
