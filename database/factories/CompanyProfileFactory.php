<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    protected $model = CompanyProfile::class;

    public function definition(): array
    {
        return [
            'recruiter_id' => User::factory()->create(['role' => 'recruiter']),
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'wilaya' => fake()->city(),
            'address' => fake()->address(),
            'logo' => fake()->optional()->imageUrl(),
        ];
    }
}
