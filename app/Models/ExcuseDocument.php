<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcuseDocument extends Model
{
    use HasFactory;

    protected $primaryKey = 'document_id';

    public $timestamps = false;

    protected $fillable = [
        'readmission_id',
        'document_type',
        'file_path',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function readmissionRequest(): BelongsTo
    {
        return $this->belongsTo(ReadmissionRequest::class, 'readmission_id', 'readmission_id');
    }
}
