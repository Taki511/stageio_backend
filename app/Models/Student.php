<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'university_email',
    ];

    /**
     * Get the user that owns the student profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the CV associated with the student.
     */
    public function cv()
    {
        return $this->hasOne(StudentCV::class, 'student_id');
    }

    /**
     * Get the applications submitted by the student.
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'student_id');
    }
}
