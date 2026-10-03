<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolYear extends Model
{
    use HasFactory;

    protected $primaryKey = 'school_year_id';

    public $timestamps = false;

    protected $fillable = [
        'year_label',
        'semester',
        'start_date',
        'end_date',
        'is_archived',
        'archived_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_archived' => 'boolean',
            'archived_date' => 'date',
        ];
    }

    /** All course offerings scheduled under this school year. */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'school_year_id', 'school_year_id');
    }
}
