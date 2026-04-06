<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        $skills = ['React', 'Java', 'Python', 'PHP', 'Laravel', 'Vue.js', 'Angular', 'Node.js', 'JavaScript', 'TypeScript', 'C++', 'C#', 'Go', 'Rust', 'Swift'];

        return [
            'name' => fake()->unique()->randomElement($skills),
        ];
    }
}
