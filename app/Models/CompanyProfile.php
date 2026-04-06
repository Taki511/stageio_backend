<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'recruiter_id',
        'name',
        'description',
        'wilaya',
        'address',
        'logo',
    ];

    /**
     * Get the recruiter that manages the company profile.
     */
    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    /**
     * Get the internship offers posted by the company.
     */
    public function internshipOffers()
    {
        return $this->hasMany(InternshipOffer::class, 'company_profile_id');
    }
}
