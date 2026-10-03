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

    public $timestamps = false;

    protected $fillable = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function processedReadmissionRequests(): HasMany
    {
        return $this->hasMany(ReadmissionRequest::class, 'processed_by', 'osa_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'osa_id', 'osa_id');
    }
}
