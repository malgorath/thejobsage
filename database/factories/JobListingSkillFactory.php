<?php

namespace Database\Factories;

use App\Models\JobListingSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobListingSkill>
 */
class JobListingSkillFactory extends Factory
{
    protected $model = JobListingSkill::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }
}
