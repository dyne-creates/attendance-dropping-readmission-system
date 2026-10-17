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

    public function setRoomAttribute(?string $room): void
    {
        $normalizedRoom = trim((string) $room);

        $this->attributes['room'] = $normalizedRoom === '' ? null : $normalizedRoom;
        $this->attributes['room_key'] = $normalizedRoom === '' ? null : mb_strtolower($normalizedRoom);
    }

    /** The course this meeting slot belongs to. */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}
