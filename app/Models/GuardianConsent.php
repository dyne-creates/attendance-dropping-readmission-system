<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianConsent extends Model
{
    use HasFactory;

    protected $primaryKey = 'consent_id';

    public $timestamps = false;

    protected $fillable = [
        'guardian_id',
        'consent_type',
        'consent_form_path',
        'consented_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id', 'guardian_id');
    }
}
