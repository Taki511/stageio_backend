<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    /** @use HasFactory<\Database\Factories\SkillFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the internship offers that require this skill.
     */
    public function internshipOffers()
    {
        return $this->belongsToMany(InternshipOffer::class, 'internship_offer_skill');
    }
}
