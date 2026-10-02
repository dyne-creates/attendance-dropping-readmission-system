<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DroppingTransaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'drop_id';

    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'dropped_by',
        'absence_hours_at_drop',
        'sequence_in_cycle',
        'drop_date',
    ];

    protected function casts(): array
    {
        return [
            'absence_hours_at_drop' => 'decimal:2',
            'drop_date' => 'date',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    public function dropper(): BelongsTo
    {
        return $this->belongsTo(FacultyMember::class, 'dropped_by', 'faculty_id');
    }

    public function readmissionRequest(): HasOne
    {
        return $this->hasOne(ReadmissionRequest::class, 'drop_id', 'drop_id');
    }
}
