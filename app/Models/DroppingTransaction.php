<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DroppingTransaction extends Model
{
    use HasFactory;

    public const TYPE_WEEKLY_HOURS = 'weekly_hours';

    public const TYPE_PERMANENT = 'permanent';

    public const TYPE_INSTRUCTOR_REFERRAL = 'instructor_referral';

    protected $primaryKey = 'drop_id';

    protected $fillable = [
        'enrollment_id',
        'dropped_by',
        'absence_hours_at_drop',
        'sequence_in_cycle',
        'drop_date',
        'drop_type',
        'instructor_reason',
    ];

    protected function casts(): array
    {
        return [
            'absence_hours_at_drop' => 'decimal:2',
            'drop_date' => 'date',
        ];
    }

    /** The enrollment that was dropped. */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    /** The faculty member who performed the drop. */
    public function dropper(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class, 'dropped_by', 'faculty_id');
    }

    /** The readmission request this drop led to, if any. */
    public function readmissionRequest(): HasOne
    {
        return $this->hasOne(ReadmissionRequest::class, 'drop_id', 'drop_id')->latestOfMany('attempt_number');
    }

    /** All readmission attempts for this drop, oldest first. */
    public function readmissionRequests(): HasMany
    {
        return $this->hasMany(ReadmissionRequest::class, 'drop_id', 'drop_id')
            ->orderBy('attempt_number');
    }
}
