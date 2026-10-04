<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A supporting file (medical certificate, excuse letter) the student
 * attaches to a readmission request to explain their absences.
 */
class ExcuseDocument extends Model
{
    use HasFactory;

    protected $primaryKey = 'document_id';

    protected $fillable = [
        'readmission_id',
        'document_type',
        'file_path',
    ];

    /** The readmission request this document supports. */
    public function readmissionRequest(): BelongsTo
    {
        return $this->belongsTo(ReadmissionRequest::class, 'readmission_id', 'readmission_id');
    }
}
