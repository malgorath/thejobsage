<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_id' => Job::factory(),
            'resume_id' => function (array $attributes) {
                return Resume::factory()->create(['user_id' => $attributes['user_id']]);
            },
            'status' => $this->faker->randomElement(['pending', 'reviewed', 'accepted', 'rejected']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
