<?php

use Database\Seeders\PromptSeeder;

test('prompt seeder seeds blind-screening prompts', function () {
    $this->seed(PromptSeeder::class);

    $this->assertDatabaseHas('prompts', ['key' => 'skill_extraction']);
    $this->assertDatabaseHas('prompts', ['key' => 'job_skill_extraction']);
    $this->assertDatabaseHas('prompts', ['key' => 'pii_strip']);
    $this->assertDatabaseHas('prompts', ['key' => 'candidate_summary']);
    $this->assertDatabaseHas('prompts', ['key' => 'skill_gap_summary']);
});

test('prompt seeder does not seed retired job-seeker prompts', function () {
    $this->seed(PromptSeeder::class);

    $this->assertDatabaseMissing('prompts', ['key' => 'resume_analysis']);
    $this->assertDatabaseMissing('prompts', ['key' => 'job_match']);
    $this->assertDatabaseMissing('prompts', ['key' => 'cover_letter']);
});
