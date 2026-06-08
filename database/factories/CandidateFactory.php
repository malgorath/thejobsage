<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'resume_id' => Resume::factory(),
            'uploaded_by' => User::factory()->create(['role' => 'recruiter'])->id,
            'anonymized_summary' => $this->faker->paragraph(),
            'match_score' => $this->faker->numberBetween(0, 100),
            'status' => 'analyzed',
        ];
    }
}
