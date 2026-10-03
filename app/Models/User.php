<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'role',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', 
        ];
    }


    /** The student profile for this login, if role = student. */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }

    /** The faculty profile for this login, if role = faculty. */
    public function facultyMember(): HasOne
    {
        return $this->hasOne(FacultyMember::class, 'user_id', 'user_id');
    }

    /** The OSA staff profile for this login, if role = osa_staff. */
    public function osaStaff(): HasOne
    {
        return $this->hasOne(OsaStaff::class, 'user_id', 'user_id');
    }
}