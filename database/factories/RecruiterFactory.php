<?php

namespace Database\Factories;

use App\Models\Recruiter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recruiter>
 */
class RecruiterFactory extends Factory
{
    protected $model = Recruiter::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_email' => fake()->unique()->safeEmail(),
        ];
    }
}
