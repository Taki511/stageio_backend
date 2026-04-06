<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recruiter extends Model
{
    /** @use HasFactory<\Database\Factories\RecruiterFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'company_email',
    ];

    /**
     * Get the user that owns the recruiter profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company profile managed by the recruiter.
     */
    public function companyProfile()
    {
        return $this->hasOne(CompanyProfile::class, 'recruiter_id');
    }
}
