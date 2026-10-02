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

    public $timestamps = false;

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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(GuardianConsent::class, 'guardian_id', 'guardian_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'guardian_id', 'guardian_id');
    }
}
