<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'student_number',
        'last_name',
        'first_name',
        'honorifics',
        'department',
        'year_level',
        'program',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}".($this->honorifics ? ", {$this->honorifics}" : ''));
    }

    /** The login account this profile belongs to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Guardians this student has on file (for drop/readmission contact). */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class, 'student_id', 'student_id');
    }

    /** Every course enrollment this student has ever had. */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id', 'student_id');
    }

    /** Notifications sent directly to this student (drop/readmission notices). */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'student_id', 'student_id');
    }
}
