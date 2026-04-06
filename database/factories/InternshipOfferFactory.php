<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use App\Models\InternshipOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipOffer>
 */
class InternshipOfferFactory extends Factory
{
    protected $model = InternshipOffer::class;

    public function definition(): array
    {
        return [
            'company_profile_id' => CompanyProfile::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(3, true),
            'wilaya' => fake()->city(),
            'start_date' => fake()->dateTimeBetween('now', '+3 months'),
            'internship_type' => fake()->randomElement(['full_time', 'part_time', 'remote']),
            'duration' => fake()->numberBetween(4, 24), // weeks
        ];
    }
}
