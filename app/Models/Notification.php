<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'readmission_id',
        'osa_id',
        'student_id',
        'guardian_id',
        'notification_type',
        'channel',
        'message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /** The readmission request this notice relates to, if any. */
    public function readmissionRequest(): BelongsTo
    {
        return $this->belongsTo(ReadmissionRequest::class, 'readmission_id', 'readmission_id');
    }

    /** The OSA staff member who sent this notification, if any. */
    public function osaStaff(): BelongsTo
    {
        return $this->belongsTo(OsaStaff::class, 'osa_id', 'osa_id');
    }

    /** The student this notification was sent to, if applicable. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /** The guardian this notification was sent to, if applicable. */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id', 'guardian_id');
    }
}