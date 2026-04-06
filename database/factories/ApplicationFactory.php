<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\InternshipOffer;
use App\Models\StudentCV;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory()->create(['role' => 'student']),
            'internship_offer_id' => InternshipOffer::factory(),
            'student_cv_id' => StudentCV::factory(),
            'application_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'status' => fake()->randomElement(['pending', 'accepted', 'refused', 'validated']),
        ];
    }
}
