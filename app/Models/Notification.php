<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $primaryKey = 'notification_id';

    public $timestamps = false;

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

    public function readmissionRequest(): BelongsTo
    {
        return $this->belongsTo(ReadmissionRequest::class, 'readmission_id', 'readmission_id');
    }

    public function osaStaff(): BelongsTo
    {
        return $this->belongsTo(OsaStaff::class, 'osa_id', 'osa_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id', 'guardian_id');
    }
}
