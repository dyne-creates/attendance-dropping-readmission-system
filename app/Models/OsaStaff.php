<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OsaStaff extends Model
{
    use HasFactory;

    protected $table = 'osa_staff';

    protected $primaryKey = 'osa_id';

    protected $fillable = [
        'user_id',
    ];

    /** The login account this profile belongs to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Readmission requests this staff member has processed. */
    public function processedReadmissionRequests(): HasMany
    {
        return $this->hasMany(ReadmissionRequest::class, 'processed_by', 'osa_id');
    }

    /** Notifications sent out by this staff member. */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'osa_id', 'osa_id');
    }
}