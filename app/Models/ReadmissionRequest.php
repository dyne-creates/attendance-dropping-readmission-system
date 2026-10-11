<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A student's request to return after being dropped (FR-05). Always
 * tied to exactly one DroppingTransaction — a student must be dropped
 * before they can readmit, enforced by the required `drop_id` FK.
 */
class ReadmissionRequest extends Model
{
    use HasFactory;

    public const REASONS = [
        'dental_medical' => 'Dental & Medical Concerns',
        'mental_health' => 'Mental Health Concerns',
        'technology_connectivity' => 'Technology & Connectivity Issues',
        'scheduling_admin' => 'Scheduling & Administrative Conflicts',
        'personal_family' => 'Personal & Family Emergencies',
        'tardiness_time' => 'Tardiness & Time Management',
        'disciplinary_sanction' => 'Disciplinary Sanction',
        'other' => 'Other',
    ];

    protected $primaryKey = 'readmission_id';

    protected $fillable = [
        'drop_id',
        'processed_by',
        'reason_code',
        'reason_details',
        'policy_acknowledged_at',
        'channel',
        // 'pending' | 'approved' | 'rejected' — defaults to 'pending'
        'status',
        'notice_sent_date',
        'processed_date',
    ];

    protected function casts(): array
    {
        return [
            'notice_sent_date' => 'date',
            'processed_date' => 'date',
            'policy_acknowledged_at' => 'datetime',
        ];
    }

    public static function reasonLabel(string $reasonCode): string
    {
        return self::REASONS[$reasonCode] ?? ucfirst(str_replace('_', ' ', $reasonCode));
    }

    /** The drop event this request is responding to. */
    public function droppingTransaction(): BelongsTo
    {
        return $this->belongsTo(DroppingTransaction::class, 'drop_id', 'drop_id');
    }

    /** The OSA staff member who processed this request, if any. */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(OsaStaff::class, 'processed_by', 'osa_id');
    }

    /** Supporting documents (medical cert, excuse letter) uploaded for this request. */
    public function excuseDocuments(): HasMany
    {
        return $this->hasMany(ExcuseDocument::class, 'readmission_id', 'readmission_id');
    }

    /** Notifications tied to this specific readmission request. */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'readmission_id', 'readmission_id');
    }
}
