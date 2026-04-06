<?php

namespace Database\Factories;

use App\Models\StudentCV;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentCV>
 */
class StudentCVFactory extends Factory
{
    protected $model = StudentCV::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->create(['role' => 'student']),
            'personal_info' => fake()->paragraph(),
            'github_link' => fake()->optional()->url(),
            'portfolio_link' => fake()->optional()->url(),
            'linkedin_link' => fake()->optional()->url(),
        ];
    }
}
