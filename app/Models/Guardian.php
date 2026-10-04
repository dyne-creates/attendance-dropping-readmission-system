<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $primaryKey = 'guardian_id';

    protected $fillable = [
        'student_id',
        'full_name',
        'relationship',
        'email',
        'phone_number',
        'is_verified',
        'verification_method',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    /** The student this guardian is attached to. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /** Consent records (e.g. "ok to share my number / drop details"). */
    public function consents(): HasMany
    {
        return $this->hasMany(GuardianConsent::class, 'guardian_id', 'guardian_id');
    }

    /** Notifications sent to this guardian. */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'guardian_id', 'guardian_id');
    }
}
