<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCV extends Model
{
    /** @use HasFactory<\Database\Factories\StudentCVFactory> */
    use HasFactory;

    protected $table = 'student_cvs';

    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'age',
        'full_address',
        'phone_number',
        'academic_level',
        'email',
        'university_email',
        'github_link',
        'linkedin_link',
        'portfolio_link',
        'motivation_letter',
        'personal_info',
        'personal_photo',
    ];

    protected $casts = [
        'age' => 'integer',
    ];

    /**
     * Get the student that owns the CV.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the applications that use this CV.
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'student_cv_id');
    }
}
