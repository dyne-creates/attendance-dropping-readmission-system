<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyMember extends Model
{
    use HasFactory;

    protected $primaryKey = 'faculty_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'department',
        // Lets a teacher silently suppress the "wrong schedule day"
        // warning once they've acknowledged it (per Ma'am Divine's request).
        'suppress_schedule_warnings',
    ];

    protected function casts(): array
    {
        return [
            'suppress_schedule_warnings' => 'boolean',
        ];
    }

    /** The login account this profile belongs to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Courses this faculty member teaches. */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'faculty_id', 'faculty_id');
    }

    /** Attendance entries this faculty member has recorded. */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'recorded_by', 'faculty_id');
    }

    /** Drop transactions this faculty member has initiated. */
    public function droppingTransactions(): HasMany
    {
        return $this->hasMany(DroppingTransaction::class, 'dropped_by', 'faculty_id');
    }
}
