<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\InternshipAgreement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipAgreement>
 */
class InternshipAgreementFactory extends Factory
{
    protected $model = InternshipAgreement::class;

    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'admin_id' => User::factory()->create(['role' => 'admin']),
            'generated_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'signature_status' => fake()->boolean(),
            'pdf_file' => fake()->optional()->filePath(),
        ];
    }
}
