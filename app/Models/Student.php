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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class, 'student_id', 'student_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id', 'student_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'student_id', 'student_id');
    }
}
