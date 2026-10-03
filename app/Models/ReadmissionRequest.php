<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadmissionRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'readmission_id';

    public $timestamps = false;

    protected $fillable = [
        'drop_id',
        'processed_by',
        'reason_code',
        'reason_details',
        'channel',
        'status',
        'notice_sent_date',
        'processed_date',
    ];

    protected function casts(): array
    {
        return [
            'notice_sent_date' => 'date',
            'processed_date' => 'date',
        ];
    }

    public function droppingTransaction(): BelongsTo
    {
        return $this->belongsTo(DroppingTransaction::class, 'drop_id', 'drop_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(OsaStaff::class, 'processed_by', 'osa_id');
    }

    public function excuseDocuments(): HasMany
    {
        return $this->hasMany(ExcuseDocument::class, 'readmission_id', 'readmission_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'readmission_id', 'readmission_id');
    }
}
