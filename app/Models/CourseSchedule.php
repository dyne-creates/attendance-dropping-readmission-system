<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSchedule extends Model
{
    use HasFactory;

    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'course_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
    ];

    /** The course this meeting slot belongs to. */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}